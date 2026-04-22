<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Extensions\Services\ExtensionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (
            class_exists(ExtensionService::class)
            && Schema::hasTable('extensions')
        ) {
            app(ExtensionService::class)->ensureCatalogSeeded();
        }

        View::composer('layouts.global', function ($view): void {
            $apps = collect();

            if (
                Auth::check()
                && class_exists(TenantExtension::class)
                && Schema::hasTable('tenant_extensions')
                && Schema::hasTable('extensions')
            ) {
                $tenantId = (int) Auth::user()->tenant_id;

                $routeMap = [
                    'clients' => ['route' => 'clients.index', 'icon' => 'fa-users', 'icon_bg_color' => '#2563eb'],
                    'stock' => ['route' => 'stock.articles.index', 'icon' => 'fa-boxes-stacked', 'icon_bg_color' => '#0891b2'],
                    'invoice' => ['route' => 'invoices.index', 'icon' => 'fa-file-invoice', 'icon_bg_color' => '#7c3aed'],
                    'projects' => ['route' => 'projects.index', 'icon' => 'fa-diagram-project', 'icon_bg_color' => '#0ea5e9'],
                    'notion-workspace' => ['route' => 'notion-workspace.index', 'icon' => 'fa-book-open', 'icon_bg_color' => '#111827'],
                    'google-drive' => ['route' => 'google-drive.index', 'icon' => 'fa-google-drive', 'icon_bg_color' => '#4285F4'],
                    'gdrive' => ['route' => 'google-drive.index', 'icon' => 'fa-google-drive', 'icon_bg_color' => '#4285F4'],
                    'google-calendar' => ['route' => 'google-calendar.index', 'icon' => 'fa-calendar-days', 'icon_bg_color' => '#4285F4'],
                    'google-sheets' => ['route' => 'google-sheets.index', 'icon' => 'fa-file-excel', 'icon_bg_color' => '#0f9d58'],
                    'google-docx' => ['route' => 'google-docx.index', 'icon' => 'fa-file-word', 'icon_bg_color' => '#1a73e8'],
                    'google-gmail' => ['route' => 'google-gmail.index', 'icon' => 'fa-envelope-open-text', 'icon_bg_color' => '#ea4335'],
                ];

                $apps = TenantExtension::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('status', ['active', 'trial'])
                    ->with('extension')
                    ->get()
                    ->filter(fn ($activation) => $activation->extension !== null)
                    ->map(function ($activation) use ($routeMap) {
                        $extension = $activation->extension;
                        $slug = (string) $extension->slug;
                        $map = $routeMap[$slug] ?? null;
                        $targetRoute = $map['route'] ?? null;
                        $url = null;

                        if ($targetRoute && Route::has($targetRoute)) {
                            $url = route($targetRoute);
                        } elseif (Route::has('marketplace.show')) {
                            $url = route('marketplace.show', $slug);
                        }

                        $extIcon = (string) ($extension->icon ?? '');
                        $icon = str_starts_with($extIcon, 'fa-')
                            ? $extIcon
                            : (string) ($map['icon'] ?? 'fa-puzzle-piece');
                        $iconBgColor = (string) ($extension->icon_bg_color ?? ($map['icon_bg_color'] ?? '#334155'));

                        return (object) [
                            'slug' => $slug,
                            'name' => (string) $extension->name,
                            'icon' => $icon,
                            'icon_bg_color' => $iconBgColor,
                            'url' => $url,
                            'status' => (string) $activation->status,
                            'sort_order' => (int) ($extension->sort_order ?? 9999),
                        ];
                    })
                    ->filter(fn ($app) => !empty($app->url))
                    ->sortBy(fn ($app) => sprintf('%05d-%s', (int) ($app->sort_order ?? 9999), mb_strtolower((string) $app->name)))
                    ->values();
            }

            $view->with('layoutInstalledApps', $apps);
            $view->with('layoutInstalledAppsCount', $apps->count());
        });
    }
}
