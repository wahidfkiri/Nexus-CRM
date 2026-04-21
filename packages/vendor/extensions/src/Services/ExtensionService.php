<?php

namespace Vendor\Extensions\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Extensions\Repositories\ExtensionRepository;
use Vendor\Extensions\Events\ExtensionActivated;
use Vendor\Extensions\Events\ExtensionDeactivated;
use Vendor\Extensions\Events\ExtensionSuspended;

class ExtensionService
{
    public function __construct(protected ExtensionRepository $repository) {}

    // ── Catalogue CRUD (super-admin) ────────────────────────────────────────

    public function createExtension(array $data): Extension
    {
        return DB::transaction(function () use ($data) {
            // Générer slug si absent
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            // Assurer unicité du slug
            $base  = $data['slug'];
            $count = 1;
            while (Extension::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $base . '-' . $count++;
            }

            $extension = $this->repository->create($data);

            Log::channel('daily')->info("[Extension] Créée : {$extension->slug}");

            return $extension;
        });
    }

    public function updateExtension(Extension $extension, array $data): Extension
    {
        return DB::transaction(function () use ($extension, $data) {
            // Upload icône
            if (!empty($data['icon_file'])) {
                if ($extension->icon && !str_starts_with($extension->icon, 'fa-')) {
                    Storage::disk('public')->delete($extension->icon);
                }
                $data['icon'] = $data['icon_file']->store(
                    config('extensions.upload.icon_path', 'extensions/icons'),
                    config('extensions.upload.disk', 'public')
                );
                unset($data['icon_file']);
            }

            // Upload banner
            if (!empty($data['banner_file'])) {
                if ($extension->banner) {
                    Storage::disk('public')->delete($extension->banner);
                }
                $data['banner'] = $data['banner_file']->store(
                    config('extensions.upload.banner_path', 'extensions/banners'),
                    config('extensions.upload.disk', 'public')
                );
                unset($data['banner_file']);
            }

            return $this->repository->update($extension, $data);
        });
    }

    public function deleteExtension(Extension $extension): bool
    {
        if ($extension->active_installs_count > 0) {
            throw new \RuntimeException(
                "Impossible de supprimer une extension avec {$extension->active_installs_count} installation(s) active(s)."
            );
        }
        return DB::transaction(fn() => $this->repository->delete($extension));
    }

    public function toggleFeatured(Extension $extension): Extension
    {
        return $this->repository->update($extension, ['is_featured' => !$extension->is_featured]);
    }

    public function toggleStatus(Extension $extension): Extension
    {
        $newStatus = $extension->status === 'active' ? 'inactive' : 'active';
        return $this->repository->update($extension, ['status' => $newStatus]);
    }

    public function getStats(): array
    {
        return $this->repository->getStats();
    }

    // ── Marketplace / Tenant ────────────────────────────────────────────────

    public function getMarketplace(array $filters, int $tenantId)
    {
        $perPage = min((int)($filters['per_page'] ?? 20), 100);
        return $this->repository->getMarketplace($filters, $tenantId, $perPage);
    }

    public function getTenantExtensions(int $tenantId, array $filters = [])
    {
        return $this->repository->getTenantExtensions($tenantId, $filters);
    }

    // ── Activation ──────────────────────────────────────────────────────────

    public function activate(Extension $extension, int $tenantId, int $userId, array $options = []): TenantExtension
    {
        return DB::transaction(function () use ($extension, $tenantId, $userId, $options) {
            if ($extension->status !== 'active') {
                throw new \RuntimeException('Cette extension n\'est pas disponible.');
            }

            $existing = $this->repository->getTenantActivation($tenantId, $extension->id);

            if ($existing && in_array($existing->status, ['active', 'trial'])) {
                throw new \RuntimeException('Cette extension est déjà activée.');
            }

            // Déterminer le mode (trial ou direct)
            $isTrial  = $extension->has_trial && !$existing;
            $status   = $isTrial ? 'trial' : 'active';

            $activationData = [
                'tenant_id'     => $tenantId,
                'extension_id'  => $extension->id,
                'activated_by'  => $userId,
                'status'        => $status,
                'activated_at'  => now(),
                'billing_cycle' => $options['billing_cycle'] ?? $extension->billing_cycle,
                'price_paid'    => $options['price_paid']    ?? $extension->price,
                'currency'      => $extension->currency,
            ];

            if ($isTrial) {
                $activationData['trial_ends_at'] = now()->addDays($extension->trial_days);
            }

            if ($existing) {
                $activation = $this->repository->updateActivation($existing, $activationData);
            } else {
                $activation = $this->repository->createActivation($activationData);
            }

            // Mettre à jour les compteurs
            $extension->incrementInstalls();

            // Log
            $this->repository->logActivity([
                'extension_id' => $extension->id,
                'tenant_id'    => $tenantId,
                'user_id'      => $userId,
                'event'        => $isTrial ? 'trial_started' : 'activated',
                'payload'      => ['billing_cycle' => $activationData['billing_cycle']],
            ]);

            event(new ExtensionActivated($activation));

            Log::channel('daily')->info("[Extension] Activée : {$extension->slug} pour tenant #{$tenantId}");

            return $activation->fresh(['extension']);
        });
    }

    public function deactivate(TenantExtension $activation, int $userId, string $reason = ''): TenantExtension
    {
        return DB::transaction(function () use ($activation, $userId, $reason) {
            $result = $this->repository->updateActivation($activation, [
                'status'         => 'inactive',
                'deactivated_at' => now(),
            ]);

            $activation->extension->decrementActiveInstalls();

            $this->repository->logActivity([
                'extension_id' => $activation->extension_id,
                'tenant_id'    => $activation->tenant_id,
                'user_id'      => $userId,
                'event'        => 'deactivated',
                'payload'      => ['reason' => $reason],
            ]);

            event(new ExtensionDeactivated($activation));

            return $result;
        });
    }

    public function suspend(TenantExtension $activation, string $reason, string $suspendedBy): TenantExtension
    {
        return DB::transaction(function () use ($activation, $reason, $suspendedBy) {
            $result = $this->repository->updateActivation($activation, [
                'status'           => 'suspended',
                'suspended_at'     => now(),
                'suspension_reason'=> $reason,
                'suspended_by'     => $suspendedBy,
            ]);

            $this->repository->logActivity([
                'extension_id' => $activation->extension_id,
                'tenant_id'    => $activation->tenant_id,
                'user_id'      => null,
                'event'        => 'suspended',
                'payload'      => ['reason' => $reason],
            ]);

            event(new ExtensionSuspended($activation));

            return $result;
        });
    }

    public function saveSettings(TenantExtension $activation, array $settings): TenantExtension
    {
        $activation = $this->repository->updateActivation($activation, ['settings' => $settings]);

        $this->repository->logActivity([
            'extension_id' => $activation->extension_id,
            'tenant_id'    => $activation->tenant_id,
            'user_id'      => auth()->id(),
            'event'        => 'configured',
        ]);

        return $activation;
    }

    // ── SuperAdmin activations overview ────────────────────────────────────

    public function getAllActivations(array $filters)
    {
        $perPage = min((int)($filters['per_page'] ?? 20), 100);
        return $this->repository->getAllActivationsPaginated($filters, $perPage);
    }
}