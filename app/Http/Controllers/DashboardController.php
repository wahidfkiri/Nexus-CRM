<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectActivity;
use NexusExtensions\Projects\Models\ProjectTask;
use Vendor\Client\Models\Client;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Payment;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\Order;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Session expirée');
        }

        if ($user->tenant_id && OnboardingController::mustCompleteOnboarding($user)) {
            return redirect()->route('onboarding.show');
        }

        $tenant = $user->tenant;
        $tenantCurrency = strtoupper((string) ($tenant->currency ?? 'EUR'));

        $startThisMonth = now()->startOfMonth();
        $endNow = now();
        $startPrevMonth = now()->subMonthNoOverflow()->startOfMonth();
        $endPrevMonth = now()->subMonthNoOverflow()->endOfMonth();

        $clientCount = 0;
        $newClientsThisMonth = 0;
        $newClientsPrevMonth = 0;

        $invoiceCount = 0;
        $unpaidInvoiceCount = 0;
        $pendingAmount = 0.0;
        $invoicesThisMonth = 0;
        $invoicesPrevMonth = 0;
        $paymentsThisMonth = 0.0;
        $paymentsPrevMonth = 0.0;

        $projectCount = 0;
        $activeProjectCount = 0;
        $tasksDueSoon = 0;

        $stockItemCount = 0;
        $lowStockCount = 0;
        $supplierOrderPending = 0;

        $recentInvoices = collect();
        $upcomingTasks = collect();
        $history = collect();

        if (Schema::hasTable('clients')) {
            $clientQuery = Client::query();
            $clientCount = (int) (clone $clientQuery)->count();
            $newClientsThisMonth = (int) (clone $clientQuery)->whereBetween('created_at', [$startThisMonth, $endNow])->count();
            $newClientsPrevMonth = (int) (clone $clientQuery)->whereBetween('created_at', [$startPrevMonth, $endPrevMonth])->count();

            $history = $history->merge(
                Client::query()
                    ->latest('created_at')
                    ->limit(8)
                    ->get(['id', 'company_name', 'contact_name', 'created_at'])
                    ->map(function (Client $client) {
                        return [
                            'at' => $client->created_at,
                            'icon' => 'fa-users',
                            'title' => 'Nouveau client ajouté',
                            'description' => $client->company_name ?: ($client->contact_name ?: 'Client sans nom'),
                            'url' => $this->routeIfExists('clients.show', ['client' => $client->id]),
                        ];
                    })
            );
        }

        if (Schema::hasTable('invoices')) {
            $invoiceQuery = Invoice::query();
            $invoiceCount = (int) (clone $invoiceQuery)->count();
            $unpaidInvoiceCount = (int) (clone $invoiceQuery)->whereNotIn('status', ['paid', 'cancelled', 'refunded'])->count();
            $pendingAmount = (float) ((clone $invoiceQuery)->whereNotIn('status', ['paid', 'cancelled', 'refunded'])->sum('amount_due'));
            $invoicesThisMonth = (int) (clone $invoiceQuery)->whereBetween('issue_date', [$startThisMonth->toDateString(), $endNow->toDateString()])->count();
            $invoicesPrevMonth = (int) (clone $invoiceQuery)->whereBetween('issue_date', [$startPrevMonth->toDateString(), $endPrevMonth->toDateString()])->count();

            $recentInvoices = Invoice::query()
                ->with('client:id,company_name')
                ->latest('created_at')
                ->limit(6)
                ->get(['id', 'client_id', 'number', 'status', 'total', 'currency', 'issue_date', 'created_at']);

            $history = $history->merge(
                Invoice::query()
                    ->with('client:id,company_name')
                    ->latest('created_at')
                    ->limit(8)
                    ->get(['id', 'client_id', 'number', 'status', 'total', 'currency', 'created_at'])
                    ->map(function (Invoice $invoice) {
                        return [
                            'at' => $invoice->created_at,
                            'icon' => 'fa-file-invoice',
                            'title' => 'Nouvelle facture',
                            'description' => trim(($invoice->number ?: 'Facture') . ' · ' . ($invoice->client?->company_name ?: 'Sans client')),
                            'url' => $this->routeIfExists('invoices.show', ['invoice' => $invoice->id]),
                        ];
                    })
            );
        }

        if (Schema::hasTable('payments')) {
            $paymentQuery = Payment::query();
            $paymentsThisMonth = (float) (clone $paymentQuery)->whereBetween('payment_date', [$startThisMonth->toDateString(), $endNow->toDateString()])->sum('amount');
            $paymentsPrevMonth = (float) (clone $paymentQuery)->whereBetween('payment_date', [$startPrevMonth->toDateString(), $endPrevMonth->toDateString()])->sum('amount');

            $history = $history->merge(
                Payment::query()
                    ->with('invoice:id,number')
                    ->latest('created_at')
                    ->limit(8)
                    ->get(['id', 'invoice_id', 'amount', 'currency', 'created_at'])
                    ->map(function (Payment $payment) {
                        return [
                            'at' => $payment->created_at,
                            'icon' => 'fa-money-check-dollar',
                            'title' => 'Paiement enregistré',
                            'description' => trim(
                                number_format((float) $payment->amount, 2, ',', ' ')
                                . ' '
                                . strtoupper((string) ($payment->currency ?: 'EUR'))
                                . ($payment->invoice?->number ? ' · ' . $payment->invoice->number : '')
                            ),
                            'url' => $payment->invoice_id
                                ? $this->routeIfExists('invoices.show', ['invoice' => $payment->invoice_id])
                                : null,
                        ];
                    })
            );
        }

        if (Schema::hasTable('projects')) {
            $projectQuery = Project::query();
            $projectCount = (int) (clone $projectQuery)->count();
            $activeProjectCount = (int) (clone $projectQuery)->whereIn('status', ['planning', 'active', 'on_hold'])->count();

            $history = $history->merge(
                Project::query()
                    ->latest('created_at')
                    ->limit(6)
                    ->get(['id', 'name', 'status', 'created_at'])
                    ->map(function (Project $project) {
                        return [
                            'at' => $project->created_at,
                            'icon' => 'fa-diagram-project',
                            'title' => 'Projet mis en place',
                            'description' => $project->name ?: 'Projet sans titre',
                            'url' => $this->routeIfExists('projects.show', ['project' => $project->id]),
                        ];
                    })
            );
        }

        if (Schema::hasTable('project_tasks')) {
            $nextWeek = now()->addDays(7)->toDateString();
            $today = now()->toDateString();

            $taskQuery = ProjectTask::query();
            $tasksDueSoon = (int) (clone $taskQuery)
                ->whereNotIn('status', ['done'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $nextWeek])
                ->count();

            $upcomingTasks = ProjectTask::query()
                ->with('project:id,name')
                ->whereNotIn('status', ['done'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $nextWeek])
                ->orderBy('due_date')
                ->limit(6)
                ->get(['id', 'project_id', 'title', 'status', 'priority', 'due_date']);
        }

        if (Schema::hasTable('project_activities')) {
            $history = $history->merge(
                ProjectActivity::query()
                    ->with('project:id,name', 'user:id,name')
                    ->latest('created_at')
                    ->limit(10)
                    ->get(['id', 'project_id', 'user_id', 'event', 'description', 'created_at'])
                    ->map(function (ProjectActivity $activity) {
                        return [
                            'at' => $activity->created_at,
                            'icon' => 'fa-list-check',
                            'title' => 'Activité projet',
                            'description' => trim(($activity->description ?: $activity->event ?: 'Mise à jour') . ($activity->project?->name ? ' · ' . $activity->project->name : '')),
                            'url' => $activity->project_id
                                ? $this->routeIfExists('projects.show', ['project' => $activity->project_id])
                                : null,
                        ];
                    })
            );
        }

        if (Schema::hasTable('stock_articles')) {
            $articleQuery = Article::query();
            $stockItemCount = (int) (clone $articleQuery)->count();
            $lowStockCount = (int) (clone $articleQuery)->whereColumn('stock_quantity', '<=', 'min_stock')->count();
        }

        if (Schema::hasTable('stock_orders')) {
            $orderQuery = Order::query();
            $supplierOrderPending = (int) (clone $orderQuery)->whereNotIn('status', ['received', 'cancelled'])->count();
        }

        $installedApps = collect();
        if (Schema::hasTable('tenant_extensions') && Schema::hasTable('extensions')) {
            $installedApps = TenantExtension::query()
                ->where('tenant_id', (int) $user->tenant_id)
                ->whereIn('status', ['active', 'trial'])
                ->with('extension')
                ->latest('activated_at')
                ->get()
                ->filter(fn (TenantExtension $activation) => $activation->extension !== null);

            $history = $history->merge(
                $installedApps
                    ->take(8)
                    ->map(function (TenantExtension $activation) {
                        $name = (string) ($activation->extension?->name ?? 'Application');
                        return [
                            'at' => $activation->activated_at ?? $activation->created_at,
                            'icon' => 'fa-plug',
                            'title' => 'Application active',
                            'description' => $name . ' · ' . strtoupper((string) $activation->status),
                            'url' => $this->routeIfExists('marketplace.show', [(string) ($activation->extension?->slug ?? '')]),
                        ];
                    })
            );
        }

        $history = $history
            ->filter(fn (array $item) => !empty($item['at']))
            ->sortByDesc(fn (array $item) => $item['at'])
            ->take(18)
            ->values();

        $installedByCategory = $installedApps
            ->groupBy(function (TenantExtension $activation) {
                return (string) ($activation->extension?->category_label ?: 'Autre');
            })
            ->map(function (Collection $items, string $categoryLabel) {
                return [
                    'label' => $categoryLabel,
                    'count' => $items->count(),
                    'apps' => $items
                        ->map(fn (TenantExtension $a) => (string) ($a->extension?->name ?: 'Application'))
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        $statsCards = [
            [
                'label' => 'Clients',
                'value' => number_format($clientCount, 0, ',', ' '),
                'icon' => 'fa-users',
                'icon_style' => 'background:var(--c-accent-lt);color:var(--c-accent);',
                'trend' => $this->trendLabel($newClientsThisMonth, $newClientsPrevMonth, 'nouveaux ce mois'),
                'trend_type' => $this->trendType($newClientsThisMonth, $newClientsPrevMonth),
            ],
            [
                'label' => 'Factures',
                'value' => number_format($invoiceCount, 0, ',', ' '),
                'icon' => 'fa-file-invoice',
                'icon_style' => 'background:#ede9fe;color:#7c3aed;',
                'trend' => $this->trendLabel($invoicesThisMonth, $invoicesPrevMonth, 'créées ce mois'),
                'trend_type' => $this->trendType($invoicesThisMonth, $invoicesPrevMonth),
            ],
            [
                'label' => 'Encaissement Mois',
                'value' => $this->formatMoney($paymentsThisMonth, $tenantCurrency),
                'icon' => 'fa-money-check-dollar',
                'icon_style' => 'background:var(--c-success-lt);color:var(--c-success);',
                'trend' => $this->trendLabel($paymentsThisMonth, $paymentsPrevMonth, 'vs mois dernier'),
                'trend_type' => $this->trendType($paymentsThisMonth, $paymentsPrevMonth),
            ],
            [
                'label' => 'Projets Actifs',
                'value' => number_format($activeProjectCount, 0, ',', ' '),
                'icon' => 'fa-diagram-project',
                'icon_style' => 'background:#e0f2fe;color:#0369a1;',
                'trend' => number_format($tasksDueSoon, 0, ',', ' ') . ' tâches à échéance 7j',
                'trend_type' => $tasksDueSoon > 0 ? 'down' : 'up',
            ],
            [
                'label' => 'Stock Critique',
                'value' => number_format($lowStockCount, 0, ',', ' '),
                'icon' => 'fa-triangle-exclamation',
                'icon_style' => 'background:var(--c-warning-lt);color:var(--c-warning);',
                'trend' => number_format($stockItemCount, 0, ',', ' ') . ' articles au total',
                'trend_type' => $lowStockCount > 0 ? 'down' : 'up',
            ],
            [
                'label' => 'Solde à Encaisser',
                'value' => $this->formatMoney($pendingAmount, $tenantCurrency),
                'icon' => 'fa-hourglass-half',
                'icon_style' => 'background:var(--c-danger-lt);color:var(--c-danger);',
                'trend' => number_format($unpaidInvoiceCount, 0, ',', ' ') . ' factures non soldées',
                'trend_type' => $unpaidInvoiceCount > 0 ? 'down' : 'up',
            ],
        ];

        $moduleSummary = [
            ['name' => 'Clients', 'icon' => 'fa-users', 'count' => $clientCount, 'route' => $this->routeIfExists('clients.index')],
            ['name' => 'Facturation', 'icon' => 'fa-file-invoice', 'count' => $invoiceCount, 'route' => $this->routeIfExists('invoices.index')],
            ['name' => 'Stock', 'icon' => 'fa-boxes-stacked', 'count' => $stockItemCount, 'route' => $this->routeIfExists('stock.articles.index')],
            ['name' => 'Commandes Fournisseur', 'icon' => 'fa-truck-ramp-box', 'count' => $supplierOrderPending, 'route' => $this->routeIfExists('stock.orders.index')],
            ['name' => 'Projets', 'icon' => 'fa-diagram-project', 'count' => $projectCount, 'route' => $this->routeIfExists('projects.index')],
            ['name' => 'Tâches à échéance', 'icon' => 'fa-list-check', 'count' => $tasksDueSoon, 'route' => $this->routeIfExists('projects.index')],
        ];

        return view('dashboard', [
            'user' => $user,
            'tenant' => $tenant,
            'statsCards' => $statsCards,
            'recentInvoices' => $recentInvoices,
            'upcomingTasks' => $upcomingTasks,
            'history' => $history,
            'installedByCategory' => $installedByCategory,
            'moduleSummary' => $moduleSummary,
        ]);
    }

    private function routeIfExists(string $routeName, array|string $params = []): ?string
    {
        if (!Route::has($routeName)) {
            return null;
        }

        $params = is_array($params) ? $params : [$params];
        return route($routeName, $params);
    }

    private function trendLabel(float|int $current, float|int $previous, string $suffix): string
    {
        if ((float) $previous <= 0.0 && (float) $current <= 0.0) {
            return '0% ' . $suffix;
        }

        if ((float) $previous <= 0.0) {
            return '+100% ' . $suffix;
        }

        $percent = (($current - $previous) / $previous) * 100;
        $prefix = $percent > 0 ? '+' : '';

        return $prefix . number_format($percent, 0, ',', ' ') . '% ' . $suffix;
    }

    private function trendType(float|int $current, float|int $previous): string
    {
        if ((float) $current >= (float) $previous) {
            return 'up';
        }

        return 'down';
    }

    private function formatMoney(float|int $value, string $currency): string
    {
        return number_format((float) $value, 2, ',', ' ') . ' ' . strtoupper($currency);
    }
}
