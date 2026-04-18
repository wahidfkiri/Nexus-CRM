<?php

namespace Vendor\Client\Services;

use Vendor\Client\Models\Client;
use Vendor\Client\Repositories\ClientRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientService
{
    protected $repository;

    public function __construct(ClientRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Créer un client
     */
    public function create(array $data): Client
    {
        DB::beginTransaction();
        
        // try {
            $client = $this->repository->create($data);
            
            DB::commit();
            
            return $client;
            
        // } catch (\Exception $e) {
        //     DB::rollBack();
             Log::error('Client creation failed: ' . $e->getMessage());
        //     throw $e;
        // }
    }

    /**
     * Mettre à jour un client
     */
    public function update(Client $client, array $data): Client
    {
        DB::beginTransaction();
        
        try {
            $client = $this->repository->update($client, $data);
            
            DB::commit();
            
            return $client;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Client update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer un client
     */
    public function delete(Client $client): bool
    {
        DB::beginTransaction();
        
        try {
            $result = $this->repository->delete($client);
            
            DB::commit();
            
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Client deletion failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Suppression massive
     */
    public function bulkDelete(array $ids): int
    {
        DB::beginTransaction();
        
        try {
            $count = $this->repository->bulkDelete($ids);
            
            DB::commit();
            
            return $count;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mise à jour massive du statut
     */
    public function bulkStatusUpdate(array $ids, string $status): int
    {
        DB::beginTransaction();
        
        try {
            $count = $this->repository->bulkStatusUpdate($ids, $status);
            
            DB::commit();
            
            return $count;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk status update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer tous les clients
     */
    public function getAllClients()
    {
        return $this->repository->getAll();
    }

    /**
     * Récupérer les statistiques
     */
    public function getStats()
    {
        return [
            'total' => $this->repository->count(),
            'active' => $this->repository->countByStatus('actif'),
            'inactive' => $this->repository->countByStatus('inactif'),
            'pending' => $this->repository->countByStatus('en_attente'),
            'revenue_total' => $this->repository->sumRevenue(),
            'by_type' => $this->repository->countByType(),
            'by_source' => $this->repository->countBySource(),
        ];
    }

    /**
     * Récupérer les clients filtrés
     * 
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredClients(array $filters)
    {
        $perPage = $filters['per_page'] ?? config('client.pagination.per_page', 15);
        $page = $filters['page'] ?? 1;
        
        return $this->repository->getFiltered($filters, $perPage, $page);
    }

}