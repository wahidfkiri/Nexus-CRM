<?php

namespace Vendor\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Vendor\Client\Models\Client;
use Vendor\Client\Http\Requests\ClientRequest;
use Vendor\Client\Services\ClientService;
use Vendor\Client\Exports\ClientsExport;
use Vendor\Client\Imports\ClientsImport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    // //     $this->middleware('permission:view_clients')->only(['index', 'show', 'getData']);
    // //     $this->middleware('permission:create_clients')->only(['create', 'store']);
    // //     $this->middleware('permission:edit_clients')->only(['edit', 'update']);
    // //     $this->middleware('permission:delete_clients')->only(['destroy', 'bulkDelete']);
    // //     $this->middleware('permission:export_clients')->only(['exportCsv', 'exportExcel', 'exportPdf']);
     }

    /**
     * Afficher la liste des clients
     */
    public function index()
    {
        $types = config('client.client_types', []);
        $statuses = config('client.client_statuses', []);
        $sources = config('client.client_sources', []);
        
        return view('client::index', compact('types', 'statuses', 'sources'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $types = config('client.client_types', []);
        $statuses = config('client.client_statuses', []);
        $sources = config('client.client_sources', []);
        
        return view('client::create', compact('types', 'statuses', 'sources'));
    }

    /**
     * Enregistrer un nouveau client
     */
    public function store(ClientRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $data['tenant_id'] = auth()->user()->tenant_id ?? null;
            
            $client = $this->clientService->create($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Client créé avec succès',
                'data' => $client,
                'redirect' => route('clients.show', $client)
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher les détails d'un client
     */
    public function show(Client $client)
    {
        $this->authorize('view', $client);
        
        return view('client::show', compact('client'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Client $client)
    {
        $this->authorize('update', $client);
        
        $types = config('client.client_types', []);
        $statuses = config('client.client_statuses', []);
        $sources = config('client.client_sources', []);
        
        return view('client::edit', compact('client', 'types', 'statuses', 'sources'));
    }

    /**
     * Mettre à jour un client
     */
    public function update(ClientRequest $request, Client $client)
    {
        $this->authorize('update', $client);
        
        try {
            $client = $this->clientService->update($client, $request->validated());
            
            return redirect()
                ->route('clients.show', $client)
                ->with('success', 'Client mis à jour avec succès');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un client
     */
    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);
        
        try {
            $this->clientService->delete($client);
            
            return redirect()
                ->route('clients.index')
                ->with('success', 'Client supprimé avec succès');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer les données pour DataTable (AJAX)
     */
    public function getData(Request $request)
    {
        $clients = $this->clientService->getFilteredClients($request->all());
        
        return response()->json([
            'data' => $clients->items(),
            'draw' => $request->draw,
            'recordsTotal' => $clients->total(),
            'recordsFiltered' => $clients->total(),
        ]);
    }

    /**
     * Suppression massive
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:clients,id'
        ]);

        try {
            $this->clientService->bulkDelete($request->ids);
            
            return response()->json([
                'success' => true,
                'message' => 'Clients supprimés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Changement de statut massif
     */
    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => 'required|in:actif,inactif,en_attente'
        ]);

        try {
            $this->clientService->bulkStatusUpdate($request->ids, $request->status);
            
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Export CSV
     */
    public function exportCsv()
    {
        return Excel::download(new ClientsExport, 'clients_' . date('Y-m-d') . '.csv');
    }

    /**
     * Export Excel
     */
    public function exportExcel()
    {
        return Excel::download(new ClientsExport, 'clients_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export PDF
     */
    public function exportPdf()
    {
        $clients = $this->clientService->getAllClients();
        $pdf = PDF::loadView('client::exports.pdf', compact('clients'));
        
        return $pdf->download('clients_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Importer des clients
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            Excel::import(new ClientsImport, $request->file('file'));
            
            return back()->with('success', 'Clients importés avec succès');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'importation: ' . $e->getMessage());
        }
    }

    /**
     * Télécharger le template d'import
     */
    public function downloadTemplate()
    {
        $headers = [
            'company_name', 'contact_name', 'email', 'phone', 'type', 'status'
        ];
        
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_clients.csv"'
        ]);
    }

    /**
     * Recherche AJAX
     */
    public function search(Request $request)
    {
        $term = $request->get('q');
        
        $clients = Client::search($term)
            ->byTenant(auth()->user()->tenant_id)
            ->limit(10)
            ->get(['id', 'company_name', 'email', 'phone']);
        
        return response()->json($clients);
    }

    /**
     * Filtres AJAX
     */
    public function filter(Request $request)
    {
        $clients = $this->clientService->getFilteredClients($request->all());
        
        if ($request->ajax()) {
            return view('client::partials.table', compact('clients'))->render();
        }
        
        return redirect()->route('clients.index', $request->all());
    }
}