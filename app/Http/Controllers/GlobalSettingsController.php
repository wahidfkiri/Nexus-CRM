<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateGlobalSettingsRequest;
use App\Support\Security\PhoneNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Vendor\CrmCore\Models\TenantSetting;

class GlobalSettingsController extends Controller
{
    private const SETTING_KEYS = [
        'company_country',
        'company_postal_code',
        'company_city',
        'company_website',
        'company_description',
        'business_hours_start',
        'business_hours_end',
        'invoice_prefix',
        'default_tax_rate',
        'date_format',
        'notifications_email',
        'notifications_browser',
    ];

    public function __construct(protected PhoneNumberService $phoneNumbers)
    {
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        $tenant = $user?->tenant;

        abort_unless($tenant, 404);

        $settings = TenantSetting::query()
            ->where('tenant_id', (int) $tenant->id)
            ->whereIn('key', self::SETTING_KEYS)
            ->pluck('value', 'key')
            ->toArray();

        $countries = collect((array) config('onboarding.countries', []))
            ->filter(fn ($country) => !empty($country['code']))
            ->map(function ($country) {
                return [
                    'code' => strtoupper((string) ($country['code'] ?? '')),
                    'name' => (string) ($country['name'] ?? ''),
                    'dial' => (string) ($country['dial'] ?? ''),
                ];
            })
            ->values()
            ->all();

        return view('settings.global', [
            'tenant' => $tenant,
            'settings' => $settings,
            'countries' => $countries,
            'currencies' => (array) config('onboarding.currencies', []),
            'timezones' => timezone_identifiers_list(),
            'canManageTenant' => method_exists($user, 'canManageTenant') ? $user->canManageTenant() : false,
        ]);
    }

    public function update(UpdateGlobalSettingsRequest $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $tenant = $user?->tenant;

        if (!$tenant) {
            return $this->errorResponse($request, 'Tenant introuvable.', 422);
        }

        $data = $request->validated();
        $phone = trim((string) ($data['tenant_phone'] ?? ''));
        $normalizedPhone = $phone !== ''
            ? ($this->phoneNumbers->normalizeInternational($phone) ?? $phone)
            : null;

        $tenant->update([
            'name' => (string) $data['tenant_name'],
            'email' => (string) ($data['tenant_email'] ?? ''),
            'phone' => $normalizedPhone,
            'address' => (string) ($data['tenant_address'] ?? ''),
            'timezone' => (string) $data['tenant_timezone'],
            'locale' => (string) $data['tenant_locale'],
            'currency' => strtoupper((string) $data['tenant_currency']),
        ]);

        $toSave = [
            'company_country' => (string) ($data['company_country'] ?? ''),
            'company_postal_code' => (string) ($data['company_postal_code'] ?? ''),
            'company_city' => (string) ($data['company_city'] ?? ''),
            'company_website' => (string) ($data['company_website'] ?? ''),
            'company_description' => (string) ($data['company_description'] ?? ''),
            'business_hours_start' => (string) ($data['business_hours_start'] ?? ''),
            'business_hours_end' => (string) ($data['business_hours_end'] ?? ''),
            'invoice_prefix' => strtoupper((string) ($data['invoice_prefix'] ?? '')),
            'default_tax_rate' => isset($data['default_tax_rate']) ? (string) $data['default_tax_rate'] : '',
            'date_format' => (string) ($data['date_format'] ?? 'd/m/Y'),
            'notifications_email' => (string) ((int) filter_var($data['notifications_email'] ?? false, FILTER_VALIDATE_BOOL)),
            'notifications_browser' => (string) ((int) filter_var($data['notifications_browser'] ?? false, FILTER_VALIDATE_BOOL)),
        ];

        foreach ($toSave as $key => $value) {
            TenantSetting::query()->updateOrCreate(
                ['tenant_id' => (int) $tenant->id, 'key' => $key],
                ['value' => $value]
            );
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Paramètres globaux enregistrés avec succès.',
            ]);
        }

        return redirect()
            ->route('settings.global')
            ->with('success', 'Paramètres globaux enregistrés avec succès.');
    }

    private function errorResponse(Request $request, string $message, int $status): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return back()->withErrors(['settings' => $message])->withInput();
    }
}
