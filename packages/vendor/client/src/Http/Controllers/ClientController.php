<?php

namespace Vendor\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Vendor\Client\Models\Client;
use Vendor\Client\Http\Requests\ClientRequest;
use Vendor\Client\Services\ClientService;
use Vendor\Client\Exports\ClientsExport;
use Vendor\Client\Imports\ClientsImport;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ClientController extends Controller
{
    public function __construct(protected ClientService $clientService) {}

    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */

    public function index()
    {
        return view('client::index', [
            'types'    => config('client.client_types', []),
            'statuses' => config('client.client_statuses', []),
            'sources'  => config('client.client_sources', []),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  CREATE                                                             */
    /* ------------------------------------------------------------------ */

    public function create()
    {
        return view('client::create', [
            'types'    => config('client.client_types', []),
            'statuses' => config('client.client_statuses', []),
            'sources'  => config('client.client_sources', []),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                              */
    /* ------------------------------------------------------------------ */

    public function store(ClientRequest $request): JsonResponse
    {
        try {
            $data               = $request->validated();
            $data['user_id']    = auth()->id();
            $data['tenant_id']  = auth()->user()->tenant_id ?? null;

            $client = $this->clientService->create($data);

            return response()->json([
                'success'  => true,
                'message'  => 'Client créé avec succès.',
                'data'     => $client,
                'redirect' => route('clients.show', $client),
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  SHOW                                                               */
    /* ------------------------------------------------------------------ */

    public function show(Client $client)
    {
       // $this->authorize('view', $client);
        $stats = $this->clientService->getStats();
        return view('client::show', compact('client', 'stats'));
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                               */
    /* ------------------------------------------------------------------ */

    public function edit(Client $client)
    {
       // $this->authorize('update', $client);

        return view('client::edit', [
            'client'   => $client,
            'types'    => config('client.client_types', []),
            'statuses' => config('client.client_statuses', []),
            'sources'  => config('client.client_sources', []),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                             */
    /* ------------------------------------------------------------------ */

    public function update(ClientRequest $request, Client $client): JsonResponse
    {
       // $this->authorize('update', $client);

        try {
            $client = $this->clientService->update($client, $request->validated());

            return response()->json([
                'success'  => true,
                'message'  => 'Client mis à jour avec succès.',
                'data'     => $client,
                'redirect' => route('clients.show', $client),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                            */
    /* ------------------------------------------------------------------ */

    public function destroy(Client $client): JsonResponse
    {
      //  $this->authorize('delete', $client);

        try {
            $this->clientService->delete($client);
            return response()->json(['success' => true, 'message' => 'Client supprimé avec succès.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  DATA (AJAX for table)                                              */
    /* ------------------------------------------------------------------ */

    public function getData(Request $request): JsonResponse
    {
        $clients = $this->clientService->getFilteredClients($request->all());

        return response()->json([
            'data'             => $clients->items(),
            'current_page'     => $clients->currentPage(),
            'last_page'        => $clients->lastPage(),
            'per_page'         => $clients->perPage(),
            'total'            => $clients->total(),
            'from'             => $clients->firstItem(),
            'to'               => $clients->lastItem(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  STATS                                                              */
    /* ------------------------------------------------------------------ */

    public function getStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->clientService->getStats(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  BULK OPERATIONS                                                    */
    /* ------------------------------------------------------------------ */

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'exists:clients,id',
        ]);

        try {
            $count = $this->clientService->bulkDelete($request->ids);
            return response()->json(['success' => true, 'message' => "{$count} client(s) supprimé(s)."]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'exists:clients,id',
            'status' => 'required|in:actif,inactif,en_attente,suspendu',
        ]);

        try {
            $count = $this->clientService->bulkStatusUpdate($request->ids, $request->status);
            return response()->json(['success' => true, 'message' => "{$count} client(s) mis à jour."]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  EXPORTS                                                            */
    /* ------------------------------------------------------------------ */

    public function exportCsv()
    {
        return Excel::download(new ClientsExport, 'clients_' . date('Y-m-d') . '.csv');
    }

    public function exportExcel()
    {
        return Excel::download(new ClientsExport, 'clients_' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf()
    {
        $clients = $this->clientService->getAllClients();
        $pdf     = app('dompdf.wrapper')->loadView('client::exports.pdf', compact('clients'));
        return $pdf->download('clients_' . date('Y-m-d') . '.pdf');
    }

    /* ------------------------------------------------------------------ */
    /*  IMPORT                                                             */
    /* ------------------------------------------------------------------ */

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new ClientsImport, $request->file('file'));
            return response()->json(['success' => true, 'message' => 'Clients importés avec succès.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur d\'importation : ' . $e->getMessage()], 500);
        }
    }

    public function downloadTemplate()
    {
        $headers = ['company_name', 'contact_name', 'email', 'phone', 'type', 'status', 'source', 'city', 'country'];
        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['Acme Corp', 'Jean Dupont', 'jean@acme.com', '+33612345678', 'entreprise', 'actif', 'direct', 'Paris', 'France']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_clients.csv"',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  SEARCH                                                             */
    /* ------------------------------------------------------------------ */

    public function search(Request $request): JsonResponse
    {
        $term    = $request->string('q')->trim()->toString();
        $clients = Client::search($term)->limit(10)->get(['id', 'company_name', 'email', 'phone']);
        return response()->json(['success' => true, 'data' => $clients]);
    }
}
