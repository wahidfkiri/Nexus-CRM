<?php

namespace Vendor\Client\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Vendor\Client\Models\Client;
use Vendor\Client\Http\Requests\ClientRequest;
use Vendor\Client\Services\ClientService;
use Vendor\Client\Http\Resources\ClientResource;
use Vendor\Client\Exports\ClientsExport;
use Maatwebsite\Excel\Facades\Excel;

class ClientApiController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Liste des clients
     */
    public function index(Request $request)
    {
        $clients = $this->clientService->getFilteredClients($request->all());
        
        return ClientResource::collection($clients);
    }

    /**
     * Créer un client
     */
    public function store(ClientRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $data['tenant_id'] = auth()->user()->tenant_id;
            
            $client = $this->clientService->create($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Client créé avec succès',
                'data' => new ClientResource($client)
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un client
     */
    public function show(Client $client)
    {
        $this->authorize('view', $client);
        
        return new ClientResource($client);
    }

    /**
     * Mettre à jour un client
     */
    public function update(ClientRequest $request, Client $client)
    {
        $this->authorize('update', $client);
        
        try {
            $client = $this->clientService->update($client, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Client mis à jour avec succès',
                'data' => new ClientResource($client)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
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
            
            return response()->json([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
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
            $count = $this->clientService->bulkDelete($request->ids);
            
            return response()->json([
                'success' => true,
                'message' => "{$count} client(s) supprimé(s) avec succès"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
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
            'status' => 'required|in:actif,inactif,en_attente,suspendu'
        ]);

        try {
            $count = $this->clientService->bulkStatusUpdate($request->ids, $request->status);
            
            return response()->json([
                'success' => true,
                'message' => "{$count} client(s) mis à jour avec succès"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export des clients
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx');
        $filename = 'clients_' . date('Y-m-d') . '.' . $format;
        
        return Excel::download(new ClientsExport, $filename);
    }

    /**
     * Recherche de clients
     */
    public function search(Request $request)
    {
        $term = $request->get('q');
        $limit = $request->get('limit', 10);
        
        $clients = Client::search($term)
            ->byTenant(auth()->user()->tenant_id)
            ->limit($limit)
            ->get(['id', 'company_name', 'email', 'phone']);
        
        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }

    /**
     * Filtres avancés
     */
    public function filter(Request $request)
    {
        $clients = $this->clientService->getFilteredClients($request->all());
        
        return ClientResource::collection($clients);
    }

    /**
     * Statistiques
     */
    public function getStats()
    {
        $stats = $this->clientService->getStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}