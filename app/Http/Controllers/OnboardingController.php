<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vendor\CrmCore\Models\TenantSetting;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Extensions\Services\ExtensionService;

class OnboardingController extends Controller
{
    public function __construct(protected ExtensionService $extensions)
    {
    }

    public function show(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) {
            return redirect('/dashboard');
        }

        if ($this->isCompleted((int) $tenant->id)) {
            return redirect('/dashboard');
        }

        $this->extensions->ensureCatalogSeeded();

        $sectors = config('onboarding.sectors', []);
        $sector = $this->getSetting((int) $tenant->id, 'onboarding_sector');
        $recommended = $this->recommendedAppsForSector($sector);

        $apps = Extension::query()
            ->whereIn('slug', ['clients', 'stock', 'invoice', 'projects', 'notion-workspace', 'google-drive', 'google-calendar', 'google-sheets', 'google-docx', 'google-gmail'])
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $activeSlugs = TenantExtension::query()
            ->where('tenant_id', (int) $tenant->id)
            ->whereIn('status', ['active', 'trial'])
            ->with('extension:id,slug')
            ->get()
            ->pluck('extension.slug')
            ->filter()
            ->values()
            ->all();

        return view('onboarding.index', [
            'tenant' => $tenant,
            'sectors' => $sectors,
            'selectedSector' => $sector,
            'recommendedApps' => $recommended,
            'apps' => $apps,
            'activeSlugs' => $activeSlugs,
        ]);
    }

    public function saveCompany(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:60'],
            'company_address' => ['nullable', 'string', 'max:600'],
            'company_timezone' => ['required', 'string', 'max:60'],
            'company_currency' => ['required', 'string', 'max:5'],
        ]);

        $tenant = $request->user()->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant introuvable.'], 422);
        }

        $tenant->update([
            'name' => $data['company_name'],
            'email' => $data['company_email'],
            'phone' => $data['company_phone'] ?: null,
            'address' => $data['company_address'] ?: null,
            'timezone' => $data['company_timezone'],
            'currency' => strtoupper($data['company_currency']),
        ]);

        $this->setSetting((int) $tenant->id, 'onboarding_company_done', now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => 'Informations societe enregistrees.',
        ]);
    }

    public function saveSector(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sector' => ['required', 'string', 'in:' . implode(',', array_keys(config('onboarding.sectors', [])))],
        ]);

        $tenantId = (int) $request->user()->tenant_id;
        $this->setSetting($tenantId, 'onboarding_sector', $data['sector']);
        $this->setSetting($tenantId, 'onboarding_sector_done', now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => 'Secteur enregistre.',
            'recommended_apps' => $this->recommendedAppsForSector($data['sector']),
        ]);
    }

    public function saveApps(Request $request): JsonResponse
    {
        $data = $request->validate([
            'apps' => ['nullable', 'array'],
            'apps.*' => ['string', 'max:100'],
        ]);

        $tenantId = (int) $request->user()->tenant_id;
        $selected = collect($data['apps'] ?? [])->unique()->values();

        $sector = $this->getSetting($tenantId, 'onboarding_sector');
        if ($selected->isEmpty()) {
            $selected = collect($this->recommendedAppsForSector($sector));
        }

        $this->extensions->ensureCatalogSeeded();

        $available = Extension::query()
            ->whereIn('slug', ['clients', 'stock', 'invoice', 'projects', 'notion-workspace', 'google-drive', 'google-calendar', 'google-sheets', 'google-docx', 'google-gmail'])
            ->pluck('id', 'slug');

        DB::transaction(function () use ($tenantId, $selected, $available, $request): void {
            foreach ($available as $slug => $extensionId) {
                $status = $selected->contains($slug) ? 'active' : 'inactive';

                TenantExtension::query()->updateOrCreate(
                    ['tenant_id' => $tenantId, 'extension_id' => (int) $extensionId],
                    [
                        'status' => $status,
                        'activated_by' => (int) $request->user()->id,
                        'activated_at' => $status === 'active' ? now() : null,
                        'deactivated_at' => $status === 'inactive' ? now() : null,
                    ]
                );
            }
        });

        $this->setSetting($tenantId, 'onboarding_apps_done', now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => 'Applications configurees.',
            'active_slugs' => $selected->values()->all(),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $this->setSetting($tenantId, 'onboarding_completed_at', now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => 'Configuration terminee avec succes.',
            'redirect' => url('/dashboard'),
        ]);
    }

    public static function isCompletedForTenant(int $tenantId): bool
    {
        return TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', 'onboarding_completed_at')
            ->whereNotNull('value')
            ->exists();
    }

    private function isCompleted(int $tenantId): bool
    {
        return self::isCompletedForTenant($tenantId);
    }

    private function recommendedAppsForSector(?string $sector): array
    {
        $defaults = config('onboarding.defaults_by_sector', []);
        if (!$sector || !array_key_exists($sector, $defaults)) {
            return ['clients', 'invoice', 'projects', 'notion-workspace'];
        }

        return $defaults[$sector];
    }

    private function getSetting(int $tenantId, string $key): ?string
    {
        return TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->value('value');
    }

    private function setSetting(int $tenantId, string $key, string $value): void
    {
        TenantSetting::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value]
        );
    }
}
