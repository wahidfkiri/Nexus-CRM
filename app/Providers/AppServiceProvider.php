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
            $appsByCategory = collect();

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
                    'google-meet' => ['route' => 'google-meet.index', 'icon' => 'fa-video', 'icon_bg_color' => '#34a853'],
                    'slack' => ['route' => 'slack.index', 'icon' => 'fa-slack', 'icon_bg_color' => '#4A154B'],
                    'chatbot' => ['route' => 'chatbot.index', 'icon' => 'fa-comments', 'icon_bg_color' => '#0ea5e9'],
                ];

                $normalizeFaClass = static function (?string $value, string $fallback = 'fa-puzzle-piece'): string {
                    $raw = trim((string) ($value ?? ''));
                    if ($raw === '') {
                        $raw = trim($fallback);
                    }

                    $raw = preg_replace('/\s+/', ' ', $raw) ?: '';
                    $hasGlyph = preg_match('/(^|\s)fa-[a-z0-9-]+(\s|$)/i', $raw) === 1;
                    $hasFamily = preg_match('/(^|\s)(fa|fas|far|fal|fad|fab|fat|fa-solid|fa-regular|fa-light|fa-thin|fa-brands)(\s|$)/i', $raw) === 1;

                    if (!$hasGlyph) {
                        return 'fas fa-puzzle-piece';
                    }

                    if (!$hasFamily) {
                        return 'fas ' . $raw;
                    }

                    return $raw;
                };

                $apps = TenantExtension::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('status', ['active', 'trial'])
                    ->with('extension')
                    ->get()
                    ->filter(fn ($activation) => $activation->extension !== null)
                    ->map(function ($activation) use ($routeMap, $normalizeFaClass) {
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

                        $icon = $normalizeFaClass((string) ($extension->icon ?? ''), (string) ($map['icon'] ?? 'fa-puzzle-piece'));
                        $iconBgColor = (string) ($extension->icon_bg_color ?? ($map['icon_bg_color'] ?? '#334155'));
                        $categoryKey = (string) ($extension->category ?? 'other');

                        return (object) [
                            'slug' => $slug,
                            'name' => (string) $extension->name,
                            'icon' => $icon,
                            'icon_bg_color' => $iconBgColor,
                            'url' => $url,
                            'status' => (string) $activation->status,
                            'sort_order' => (int) ($extension->sort_order ?? 9999),
                            'category_key' => $categoryKey,
                            'category_label' => (string) ($extension->category_label ?? ucfirst($categoryKey)),
                            'category_icon' => $normalizeFaClass((string) ($extension->category_icon ?? ''), 'fa-puzzle-piece'),
                            'category_color' => (string) ($extension->category_color ?? '#64748b'),
                        ];
                    })
                    ->filter(fn ($app) => !empty($app->url))
                    ->sortBy(fn ($app) => sprintf('%05d-%s', (int) ($app->sort_order ?? 9999), mb_strtolower((string) $app->name)))
                    ->values();

                $categoryOrder = array_keys((array) config('extensions.categories', []));
                $orderMap = array_flip($categoryOrder);

                $appsByCategory = $apps
                    ->groupBy(fn ($app) => (string) ($app->category_key ?? 'other'))
                    ->map(function ($group, $categoryKey) {
                        $first = $group->first();
                        return (object) [
                            'key' => (string) $categoryKey,
                            'label' => (string) ($first->category_label ?? ucfirst((string) $categoryKey)),
                            'icon' => (string) ($first->category_icon ?? 'fa-puzzle-piece'),
                            'color' => (string) ($first->category_color ?? '#64748b'),
                            'apps' => $group->values(),
                        ];
                    })
                    ->sortBy(fn ($cat) => ($orderMap[$cat->key] ?? 9999) . '-' . mb_strtolower((string) $cat->label))
                    ->values();
            }

            $view->with('layoutInstalledApps', $apps);
            $view->with('layoutInstalledAppsByCategory', $appsByCategory);
            $view->with('layoutInstalledAppsCount', $apps->count());
        });
    }
}
