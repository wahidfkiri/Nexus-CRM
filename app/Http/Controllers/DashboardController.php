<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use App\Services\DraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\TrelloIntegration\Models\TrelloBoard;
use Modules\TrelloIntegration\Models\TrelloToken;
use NexusExtensions\Chatbot\Models\ChatbotMessage;
use NexusExtensions\Dropbox\Models\DropboxFile;
use NexusExtensions\Dropbox\Models\DropboxToken;
use NexusExtensions\GoogleDocx\Models\GoogleDocxDocument;
use NexusExtensions\GoogleDocx\Models\GoogleDocxToken;
use NexusExtensions\GoogleDrive\Models\GoogleDriveFile;
use NexusExtensions\GoogleDrive\Models\GoogleDriveToken;
use NexusExtensions\GoogleGmail\Models\GoogleGmailMessage;
use NexusExtensions\GoogleGmail\Models\GoogleGmailToken;
use NexusExtensions\GoogleMeet\Models\GoogleMeetMeeting;
use NexusExtensions\GoogleMeet\Models\GoogleMeetToken;
use NexusExtensions\GoogleSheets\Models\GoogleSheetsSpreadsheet;
use NexusExtensions\GoogleSheets\Models\GoogleSheetsToken;
use NexusExtensions\NotionWorkspace\Models\NotionPageLink;
use NexusExtensions\NotionWorkspace\Models\NotionWorkspaceToken;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectActivity;
use NexusExtensions\Projects\Models\ProjectTask;
use NexusExtensions\Slack\Models\SlackMessage;
use NexusExtensions\Slack\Models\SlackToken;
use Vendor\Client\Models\Client;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\GoogleCalendar\Models\GoogleCalendarEvent;
use Vendor\GoogleCalendar\Models\GoogleCalendarToken;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Payment;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\StockMovement;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Session expirée');
        }

        if ($user->tenant_id && OnboardingController::mustCompleteOnboarding($user)) {
            return redirect()->route('onboarding.show');
        }

        $currentTenantId = (int) session('current_tenant_id', $user->tenant_id ?? 0);
        $tenant = Tenant::query()->find($currentTenantId) ?: $user->tenant;
        $tenantCurrency = strtoupper((string) ($tenant->currency ?? 'EUR'));

        $installedApps = collect();
        if ($currentTenantId > 0 && Schema::hasTable('tenant_extensions') && Schema::hasTable('extensions')) {
            $installedApps = TenantExtension::query()
                ->where('tenant_id', $currentTenantId)
                ->whereIn('status', ['active', 'trial'])
                ->whereHas('extension', function ($query) {
                    $query->where('status', 'active');
                })
                ->with('extension')
                ->latest('activated_at')
                ->get()
                ->filter(fn (TenantExtension $activation) => $activation->extension !== null)
                ->values();
        }

        $installedSlugs = $installedApps
            ->pluck('extension.slug')
            ->filter()
            ->map(fn ($slug) => (string) $slug)
            ->values();

        $hasClients = $this->hasInstalled($installedSlugs, 'clients');
        $hasInvoice = $this->hasInstalled($installedSlugs, 'invoice');
        $hasStock = $this->hasInstalled($installedSlugs, 'stock');
        $hasProjects = $this->hasInstalled($installedSlugs, 'projects');

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

        $recentClients = collect();
        $criticalArticles = collect();
        $recentInvoices = collect();
        $upcomingTasks = collect();
        $history = collect();

        if ($hasClients && Schema::hasTable('clients')) {
            $clientQuery = $this->tenantQuery(Client::class, $currentTenantId);

            $clientCount = (int) (clone $clientQuery)->count();
            $newClientsThisMonth = (int) (clone $clientQuery)->whereBetween('created_at', [$startThisMonth, $endNow])->count();
            $newClientsPrevMonth = (int) (clone $clientQuery)->whereBetween('created_at', [$startPrevMonth, $endPrevMonth])->count();

            $recentClients = $this->tenantQuery(Client::class, $currentTenantId)
                ->latest('created_at')
                ->limit(6)
                ->get(['id', 'company_name', 'contact_name', 'email', 'phone', 'status', 'next_follow_up_at', 'created_at']);

            $history = $history->merge(
                $this->tenantQuery(Client::class, $currentTenantId)
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

        if ($hasInvoice && Schema::hasTable('invoices')) {
            $invoiceQuery = $this->tenantQuery(Invoice::class, $currentTenantId);

            $invoiceCount = (int) (clone $invoiceQuery)->count();
            $unpaidInvoiceCount = (int) (clone $invoiceQuery)->whereNotIn('status', ['paid', 'cancelled', 'refunded'])->count();
            $pendingAmount = (float) ((clone $invoiceQuery)->whereNotIn('status', ['paid', 'cancelled', 'refunded'])->sum('amount_due'));
            $invoicesThisMonth = (int) (clone $invoiceQuery)->whereBetween('issue_date', [$startThisMonth->toDateString(), $endNow->toDateString()])->count();
            $invoicesPrevMonth = (int) (clone $invoiceQuery)->whereBetween('issue_date', [$startPrevMonth->toDateString(), $endPrevMonth->toDateString()])->count();

            $recentInvoices = $this->tenantQuery(Invoice::class, $currentTenantId)
                ->with([
                    'client' => fn ($query) => $query
                        ->withoutGlobalScope('tenant')
                        ->select('id', 'tenant_id', 'company_name'),
                ])
                ->latest('created_at')
                ->limit(6)
                ->get(['id', 'client_id', 'number', 'status', 'total', 'currency', 'issue_date', 'created_at']);

            $history = $history->merge(
                $this->tenantQuery(Invoice::class, $currentTenantId)
                    ->with([
                        'client' => fn ($query) => $query
                            ->withoutGlobalScope('tenant')
                            ->select('id', 'tenant_id', 'company_name'),
                    ])
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

        if ($hasInvoice && Schema::hasTable('payments')) {
            $paymentQuery = $this->tenantQuery(Payment::class, $currentTenantId);

            $paymentsThisMonth = (float) (clone $paymentQuery)->whereBetween('payment_date', [$startThisMonth->toDateString(), $endNow->toDateString()])->sum('amount');
            $paymentsPrevMonth = (float) (clone $paymentQuery)->whereBetween('payment_date', [$startPrevMonth->toDateString(), $endPrevMonth->toDateString()])->sum('amount');

            $history = $history->merge(
                $this->tenantQuery(Payment::class, $currentTenantId)
                    ->with([
                        'invoice' => fn ($query) => $query
                            ->withoutGlobalScope('tenant')
                            ->select('id', 'tenant_id', 'number'),
                    ])
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

        if ($hasProjects && Schema::hasTable('projects')) {
            $projectQuery = $this->tenantQuery(Project::class, $currentTenantId);
            $projectCount = (int) (clone $projectQuery)->count();
            $activeProjectCount = (int) (clone $projectQuery)->whereIn('status', ['planning', 'active', 'on_hold'])->count();

            $history = $history->merge(
                $this->tenantQuery(Project::class, $currentTenantId)
                    ->latest('created_at')
                    ->limit(6)
                    ->get(['id', 'name', 'status', 'created_at'])
                    ->map(function (Project $project) {
                        return [
                            'at' => $project->created_at,
                            'icon' => 'fa-diagram-project',
                            'title' => 'Projet créé',
                            'description' => $project->name ?: 'Projet sans titre',
                            'url' => $this->routeIfExists('projects.show', ['project' => $project->id]),
                        ];
                    })
            );
        }

        if ($hasProjects && Schema::hasTable('project_tasks')) {
            $today = now()->toDateString();
            $nextWeek = now()->addDays(7)->toDateString();

            $taskQuery = $this->tenantQuery(ProjectTask::class, $currentTenantId);
            $tasksDueSoon = (int) (clone $taskQuery)
                ->whereNotIn('status', ['done'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $nextWeek])
                ->count();

            $upcomingTasks = $this->tenantQuery(ProjectTask::class, $currentTenantId)
                ->with([
                    'project' => fn ($query) => $query
                        ->withoutGlobalScope('tenant')
                        ->select('id', 'tenant_id', 'name'),
                ])
                ->whereNotIn('status', ['done'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $nextWeek])
                ->orderBy('due_date')
                ->limit(6)
                ->get(['id', 'project_id', 'title', 'status', 'priority', 'due_date']);
        }

        if ($hasProjects && Schema::hasTable('project_activities')) {
            $history = $history->merge(
                $this->tenantQuery(ProjectActivity::class, $currentTenantId)
                    ->with([
                        'project' => fn ($query) => $query
                            ->withoutGlobalScope('tenant')
                            ->select('id', 'tenant_id', 'name'),
                    ])
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

        if (class_exists(Draft::class) && Schema::hasTable('drafts') && $currentTenantId > 0) {
            $draftService = app(DraftService::class);

            $history = $history->merge(
                Draft::query()
                    ->forActor((int) $user->id, $currentTenantId)
                    ->notExpired()
                    ->latest('updated_at')
                    ->limit(8)
                    ->get(['id', 'type', 'route', 'updated_at'])
                    ->map(function (Draft $draft) use ($draftService) {
                        return [
                            'at' => $draft->updated_at,
                            'icon' => 'fa-pen-to-square',
                            'title' => 'Brouillon ' . ucfirst($this->draftTypeLabel((string) $draft->type)),
                            'description' => 'Formulaire non finalisé à reprendre',
                            'url' => $draftService->resolveResumeUrl($draft),
                        ];
                    })
            );
        }

        if ($hasStock && Schema::hasTable('stock_articles')) {
            $stockItemCount = (int) $this->tenantQuery(Article::class, $currentTenantId)->count();
            $lowStockCount = (int) DB::query()
                ->fromSub($this->stockArticlesQuery($currentTenantId), 'article_stocks')
                ->where('min_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->count();

            $criticalArticles = DB::query()
                ->fromSub($this->stockArticlesQuery($currentTenantId), 'article_stocks')
                ->where('min_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->orderByRaw('current_stock - min_stock asc')
                ->limit(6)
                ->get();
        }

        if ($hasStock && Schema::hasTable('stock_orders')) {
            $supplierOrderPending = (int) $this->tenantQuery(Order::class, $currentTenantId)
                ->whereNotIn('status', ['received', 'cancelled'])
                ->count();
        }

        if ($installedApps->isNotEmpty()) {
            $history = $history->merge(
                $installedApps->take(8)->map(function (TenantExtension $activation) {
                    $name = (string) ($activation->extension?->name ?: 'Application');

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
            ->filter(fn (array $item) => ! empty($item['at']))
            ->sortByDesc(fn (array $item) => $item['at'])
            ->take(18)
            ->values();

        $installedByCategory = $installedApps
            ->groupBy(fn (TenantExtension $activation) => (string) ($activation->extension?->category_label ?: 'Autre'))
            ->map(function (Collection $items, string $label) {
                return [
                    'label' => $label,
                    'count' => $items->count(),
                    'apps' => $items
                        ->map(fn (TenantExtension $activation) => (string) ($activation->extension?->name ?: 'Application'))
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        $moduleSummary = collect();
        if ($hasClients) {
            $moduleSummary->push([
                'name' => 'Clients',
                'icon' => 'fa-users',
                'count' => $clientCount,
                'route' => $this->routeIfExists('clients.index'),
                'description' => $newClientsThisMonth . ' nouveaux ce mois',
            ]);
        }
        if ($hasInvoice) {
            $moduleSummary->push([
                'name' => 'Facturation',
                'icon' => 'fa-file-invoice',
                'count' => $invoiceCount,
                'route' => $this->routeIfExists('invoices.index'),
                'description' => $unpaidInvoiceCount . ' factures ouvertes',
            ]);
        }
        if ($hasStock) {
            $moduleSummary->push([
                'name' => 'Stock',
                'icon' => 'fa-boxes-stacked',
                'count' => $stockItemCount,
                'route' => $this->routeIfExists('stock.articles.index'),
                'description' => $lowStockCount . ' articles critiques',
            ]);
            $moduleSummary->push([
                'name' => 'Commandes fournisseur',
                'icon' => 'fa-truck-ramp-box',
                'count' => $supplierOrderPending,
                'route' => $this->routeIfExists('stock.orders.index'),
                'description' => 'En attente de réception',
            ]);
        }
        if ($hasProjects) {
            $moduleSummary->push([
                'name' => 'Projets',
                'icon' => 'fa-diagram-project',
                'count' => $projectCount,
                'route' => $this->routeIfExists('projects.index'),
                'description' => $activeProjectCount . ' actifs',
            ]);
            $moduleSummary->push([
                'name' => 'Tâches à échéance',
                'icon' => 'fa-list-check',
                'count' => $tasksDueSoon,
                'route' => $this->routeIfExists('projects.index'),
                'description' => 'Sur les 7 prochains jours',
            ]);
        }

        $integrationCards = $this->buildIntegrationCards($installedApps, $currentTenantId);
        $connectedIntegrations = collect($integrationCards)->where('status', 'connected')->count();
        $attentionIntegrations = collect($integrationCards)->where('status', 'attention')->count();

        $statsCards = collect([
            [
                'label' => 'Applications actives',
                'value' => number_format($installedApps->count(), 0, ',', ' '),
                'icon' => 'fa-th-large',
                'icon_style' => 'background:#dbeafe;color:#2563eb;',
                'trend' => number_format($installedByCategory->count(), 0, ',', ' ') . ' catégories visibles',
                'trend_type' => 'up',
            ],
            [
                'label' => 'Intégrations connectées',
                'value' => number_format($connectedIntegrations, 0, ',', ' '),
                'icon' => 'fa-plug-circle-check',
                'icon_style' => 'background:#dcfce7;color:#15803d;',
                'trend' => $attentionIntegrations > 0
                    ? number_format($attentionIntegrations, 0, ',', ' ') . ' à reconnecter'
                    : 'Tout est prêt côté extensions',
                'trend_type' => $attentionIntegrations > 0 ? 'down' : 'up',
            ],
        ]);

        if ($hasClients) {
            $statsCards->push([
                'label' => 'Clients',
                'value' => number_format($clientCount, 0, ',', ' '),
                'icon' => 'fa-users',
                'icon_style' => 'background:#eff6ff;color:#2563eb;',
                'trend' => $this->trendLabel($newClientsThisMonth, $newClientsPrevMonth, 'nouveaux ce mois'),
                'trend_type' => $this->trendType($newClientsThisMonth, $newClientsPrevMonth),
            ]);
        }

        if ($hasInvoice) {
            $statsCards->push([
                'label' => 'Factures émises',
                'value' => number_format($invoiceCount, 0, ',', ' '),
                'icon' => 'fa-file-invoice',
                'icon_style' => 'background:#ede9fe;color:#7c3aed;',
                'trend' => $this->trendLabel($invoicesThisMonth, $invoicesPrevMonth, 'créées ce mois'),
                'trend_type' => $this->trendType($invoicesThisMonth, $invoicesPrevMonth),
            ]);
            $statsCards->push([
                'label' => 'Solde à encaisser',
                'value' => $this->formatMoney($pendingAmount, $tenantCurrency),
                'icon' => 'fa-hourglass-half',
                'icon_style' => 'background:#fee2e2;color:#dc2626;',
                'trend' => number_format($unpaidInvoiceCount, 0, ',', ' ') . ' factures non soldées',
                'trend_type' => $unpaidInvoiceCount > 0 ? 'down' : 'up',
            ]);
        }

        if ($hasProjects) {
            $statsCards->push([
                'label' => 'Projets actifs',
                'value' => number_format($activeProjectCount, 0, ',', ' '),
                'icon' => 'fa-diagram-project',
                'icon_style' => 'background:#e0f2fe;color:#0369a1;',
                'trend' => number_format($tasksDueSoon, 0, ',', ' ') . ' tâches à échéance sur 7 jours',
                'trend_type' => $tasksDueSoon > 0 ? 'down' : 'up',
            ]);
        }

        if ($hasStock) {
            $statsCards->push([
                'label' => 'Stock critique',
                'value' => number_format($lowStockCount, 0, ',', ' '),
                'icon' => 'fa-triangle-exclamation',
                'icon_style' => 'background:#fff7ed;color:#ea580c;',
                'trend' => number_format($stockItemCount, 0, ',', ' ') . ' articles au total',
                'trend_type' => $lowStockCount > 0 ? 'down' : 'up',
            ]);
        }

        $moduleChart = [
            'labels' => $moduleSummary->pluck('name')->values()->all(),
            'data' => $moduleSummary->pluck('count')->map(fn ($count) => (int) $count)->values()->all(),
        ];

        $financeChart = $hasInvoice ? [
            'labels' => [
                $startPrevMonth->translatedFormat('M Y'),
                $startThisMonth->translatedFormat('M Y'),
            ],
            'invoiceData' => [$invoicesPrevMonth, $invoicesThisMonth],
            'paymentData' => [round($paymentsPrevMonth, 2), round($paymentsThisMonth, 2)],
        ] : null;

        $taskChart = ($hasProjects && Schema::hasTable('project_tasks')) ? [
            'labels' => ['À faire', 'En cours', 'En revue', 'Terminées'],
            'data' => [
                (int) (clone $this->tenantQuery(ProjectTask::class, $currentTenantId))->where('status', 'todo')->count(),
                (int) (clone $this->tenantQuery(ProjectTask::class, $currentTenantId))->where('status', 'in_progress')->count(),
                (int) (clone $this->tenantQuery(ProjectTask::class, $currentTenantId))->where('status', 'review')->count(),
                (int) (clone $this->tenantQuery(ProjectTask::class, $currentTenantId))->where('status', 'done')->count(),
            ],
        ] : null;

        $stockChart = $hasStock ? [
            'labels' => ['Critique', 'Sain'],
            'data' => [
                $lowStockCount,
                max(0, $stockItemCount - $lowStockCount),
            ],
        ] : null;

        $integrationChart = [
            'labels' => ['Connectées', 'À reconnecter', 'Installées sans connexion'],
            'data' => [
                $connectedIntegrations,
                $attentionIntegrations,
                collect($integrationCards)->where('status', 'installed')->count(),
            ],
        ];

        return view('dashboard', [
            'user' => $user,
            'tenant' => $tenant,
            'statsCards' => $statsCards->values()->all(),
            'recentClients' => $recentClients,
            'criticalArticles' => $criticalArticles,
            'recentInvoices' => $recentInvoices,
            'upcomingTasks' => $upcomingTasks,
            'history' => $history,
            'installedByCategory' => $installedByCategory,
            'moduleSummary' => $moduleSummary,
            'moduleChart' => $moduleChart,
            'financeChart' => $financeChart,
            'taskChart' => $taskChart,
            'stockChart' => $stockChart,
            'integrationChart' => $integrationChart,
            'integrationCards' => $integrationCards,
            'currentTenantId' => $currentTenantId,
            'hasClients' => $hasClients,
            'hasInvoice' => $hasInvoice,
            'hasStock' => $hasStock,
            'hasProjects' => $hasProjects,
        ]);
    }

    private function tenantQuery(string $modelClass, int $tenantId)
    {
        $table = (new $modelClass())->getTable();

        return $modelClass::query()
            ->withoutGlobalScope('tenant')
            ->where($table . '.tenant_id', $tenantId);
    }

    private function stockArticlesQuery(int $tenantId)
    {
        return $this->tenantQuery(Article::class, $tenantId)
            ->select([
                'stock_articles.id',
                'stock_articles.name',
                'stock_articles.sku',
                'stock_articles.min_stock',
                'stock_articles.status',
            ])
            ->addSelect([
                'current_stock' => StockMovement::query()
                    ->withoutGlobalScope('tenant')
                    ->selectRaw("COALESCE(SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.quantity ELSE -stock_movements.quantity END), 0)")
                    ->where('stock_movements.tenant_id', $tenantId)
                    ->whereColumn('stock_movements.article_id', 'stock_articles.id'),
            ]);
    }

    private function hasInstalled(Collection $installedSlugs, array|string $needle): bool
    {
        $needles = collect(is_array($needle) ? $needle : [$needle])
            ->map(fn ($slug) => (string) $slug)
            ->all();

        return $installedSlugs->intersect($needles)->isNotEmpty();
    }

    private function buildIntegrationCards(Collection $installedApps, int $tenantId): array
    {
        $definitions = [
            'notion-workspace' => [
                'name' => 'Notion Workspace',
                'icon' => 'fa-book-open',
                'color' => '#111827',
                'token_model' => NotionWorkspaceToken::class,
                'resource_model' => NotionPageLink::class,
                'resource_label' => 'pages liées',
                'account_field' => 'notion_user_email',
                'context_field' => 'notion_workspace_name',
                'last_sync_field' => 'last_synced_at',
                'url' => '/extensions/notion-workspace',
            ],
            'trello-integration' => [
                'name' => 'Trello',
                'icon' => 'fab fa-trello',
                'color' => '#026aa7',
                'token_model' => TrelloToken::class,
                'resource_model' => TrelloBoard::class,
                'resource_label' => 'boards',
                'account_field' => 'trello_username',
                'context_field' => 'trello_full_name',
                'last_sync_field' => 'last_synced_at',
                'url' => '/extensions/trello-integration',
            ],
            'google-drive' => [
                'name' => 'Google Drive',
                'icon' => 'fa-google-drive',
                'color' => '#4285F4',
                'token_model' => GoogleDriveToken::class,
                'resource_model' => GoogleDriveFile::class,
                'resource_label' => 'fichiers',
                'account_field' => 'google_email',
                'context_field' => 'quota_formatted',
                'last_sync_field' => 'last_sync_at',
                'url' => '/extensions/google-drive',
            ],
            'dropbox' => [
                'name' => 'Dropbox',
                'icon' => 'fa-dropbox',
                'color' => '#0061FF',
                'token_model' => DropboxToken::class,
                'resource_model' => DropboxFile::class,
                'resource_label' => 'fichiers',
                'account_field' => 'dropbox_email',
                'context_field' => 'space_quota_used_gb',
                'last_sync_field' => 'last_sync_at',
                'url' => '/extensions/dropbox',
            ],
            'google-calendar' => [
                'name' => 'Google Calendar',
                'icon' => 'fa-calendar-days',
                'color' => '#4285F4',
                'token_model' => GoogleCalendarToken::class,
                'resource_model' => GoogleCalendarEvent::class,
                'resource_label' => 'événements',
                'account_field' => 'google_email',
                'context_field' => 'selected_calendar_summary',
                'last_sync_field' => 'last_sync_at',
                'url' => '/extensions/google-calendar',
            ],
            'google-sheets' => [
                'name' => 'Google Sheets',
                'icon' => 'fa-file-excel',
                'color' => '#0f9d58',
                'token_model' => GoogleSheetsToken::class,
                'resource_model' => GoogleSheetsSpreadsheet::class,
                'resource_label' => 'tableurs',
                'account_field' => 'google_email',
                'context_field' => 'google_name',
                'last_sync_field' => 'last_sync_at',
                'url' => '/extensions/google-sheets',
            ],
            'google-docx' => [
                'name' => 'Google Docs',
                'icon' => 'fa-file-word',
                'color' => '#1a73e8',
                'token_model' => GoogleDocxToken::class,
                'resource_model' => GoogleDocxDocument::class,
                'resource_label' => 'documents',
                'account_field' => 'google_email',
                'context_field' => 'google_name',
                'last_sync_field' => 'last_sync_at',
                'url' => '/extensions/google-docx',
            ],
            'google-gmail' => [
                'name' => 'Google Gmail',
                'icon' => 'fa-envelope-open-text',
                'color' => '#ea4335',
                'token_model' => GoogleGmailToken::class,
                'resource_model' => GoogleGmailMessage::class,
                'resource_label' => 'messages',
                'account_field' => 'google_email',
                'context_field' => 'google_name',
                'last_sync_field' => 'last_sync_at',
                'url' => '/extensions/google-gmail',
            ],
            'google-meet' => [
                'name' => 'Google Meet',
                'icon' => 'fa-video',
                'color' => '#34a853',
                'token_model' => GoogleMeetToken::class,
                'resource_model' => GoogleMeetMeeting::class,
                'resource_label' => 'réunions',
                'account_field' => 'google_email',
                'context_field' => 'selected_calendar_summary',
                'last_sync_field' => 'last_sync_at',
                'url' => '/extensions/google-meet',
            ],
            'slack' => [
                'name' => 'Slack',
                'icon' => 'fa-slack',
                'color' => '#4A154B',
                'token_model' => SlackToken::class,
                'resource_model' => SlackMessage::class,
                'resource_label' => 'messages',
                'account_field' => 'team_name',
                'context_field' => 'selected_channel_name',
                'last_sync_field' => 'last_sync_at',
                'url' => '/extensions/slack',
            ],
            'chatbot' => [
                'name' => 'Chatbot',
                'icon' => 'fa-comments',
                'color' => '#0ea5e9',
                'token_model' => null,
                'resource_model' => ChatbotMessage::class,
                'resource_label' => 'messages',
                'account_field' => null,
                'context_field' => null,
                'last_sync_field' => null,
                'url' => '/extensions/chatbot',
            ],
        ];

        $cards = [];

        foreach ($installedApps as $activation) {
            $slug = (string) ($activation->extension?->slug ?? '');
            if (! isset($definitions[$slug])) {
                continue;
            }

            $definition = $definitions[$slug];
            $tokenModel = $definition['token_model'];
            $resourceModel = $definition['resource_model'];

            $token = null;
            if ($tokenModel && class_exists($tokenModel) && $this->modelTableExists($tokenModel)) {
                $token = $this->tenantQuery($tokenModel, $tenantId)->latest('id')->first();
            }

            $resourceCount = 0;
            if ($resourceModel && class_exists($resourceModel) && $this->modelTableExists($resourceModel)) {
                $resourceCount = (int) $this->tenantQuery($resourceModel, $tenantId)->count();
            }

            $status = 'installed';
            $statusLabel = 'Installée';

            if ($token) {
                $isActive = isset($token->is_active) ? (bool) $token->is_active : true;
                $isExpired = method_exists($token, 'isExpired')
                    ? (bool) $token->isExpired()
                    : (bool) data_get($token, 'is_expired', false);

                if ($isActive && ! $isExpired) {
                    $status = 'connected';
                    $statusLabel = 'Connectée';
                } else {
                    $status = 'attention';
                    $statusLabel = 'À reconnecter';
                }
            } elseif ($slug === 'chatbot') {
                $status = 'connected';
                $statusLabel = 'Interne';
            }

            $account = $token && ! empty($definition['account_field'])
                ? (string) data_get($token, $definition['account_field'])
                : null;

            $context = null;
            if ($token && ! empty($definition['context_field'])) {
                $contextField = (string) $definition['context_field'];
                if ($slug === 'google-drive' && isset($token->quota_formatted)) {
                    $context = (string) $token->quota_formatted;
                } elseif ($slug === 'dropbox' && $token->space_quota_total_gb) {
                    $context = number_format((float) $token->space_quota_used_gb, 1, ',', ' ')
                        . ' Go / '
                        . number_format((float) $token->space_quota_total_gb, 1, ',', ' ')
                        . ' Go';
                } else {
                    $context = (string) data_get($token, $contextField);
                }
            }

            $lastSync = $token && ! empty($definition['last_sync_field'])
                ? data_get($token, $definition['last_sync_field'])
                : null;

            $cards[] = [
                'slug' => $slug,
                'name' => $definition['name'],
                'icon' => (string) ($activation->extension?->icon ?: $definition['icon']),
                'color' => (string) ($activation->extension?->icon_bg_color ?: $definition['color']),
                'status' => $status,
                'status_label' => $statusLabel,
                'account' => $account,
                'context' => $context,
                'resource_count' => $resourceCount,
                'resource_label' => $definition['resource_label'],
                'last_sync' => $lastSync,
                'url' => $definition['url'],
            ];
        }

        return collect($cards)
            ->sortBy([
                ['status', 'asc'],
                ['name', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function modelTableExists(string $modelClass): bool
    {
        if (! class_exists($modelClass)) {
            return false;
        }

        return Schema::hasTable((new $modelClass())->getTable());
    }

    private function routeIfExists(string $routeName, array|string $params = []): ?string
    {
        if (! Route::has($routeName)) {
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
        return (float) $current >= (float) $previous ? 'up' : 'down';
    }

    private function formatMoney(float|int $value, string $currency): string
    {
        return number_format((float) $value, 2, ',', ' ') . ' ' . strtoupper($currency);
    }

    private function draftTypeLabel(string $type): string
    {
        return (string) (config('drafts.type_labels.' . $type) ?: $type);
    }
}
