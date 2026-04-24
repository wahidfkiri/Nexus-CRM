<?php

namespace Vendor\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Vendor\Stock\Exports\ArticlesExport;
use Vendor\Stock\Exports\OrdersExport;
use Vendor\Stock\Exports\SuppliersExport;
use Vendor\Stock\Http\Requests\ArticleRequest;
use Vendor\Stock\Http\Requests\OrderRequest;
use Vendor\Stock\Http\Requests\SupplierRequest;
use Vendor\Stock\Imports\ArticlesImport;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\Supplier;
use Vendor\Stock\Services\StockService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class StockController extends Controller
{
    public function __construct(protected StockService $service) {}

    public function stats(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->stats()]);
    }

    public function articlesIndex()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        return view('stock::articles.index', [
            'statuses' => config('stock.article_statuses', []),
            'marketplaceSuggestions' => array_values(array_filter([
                $this->makeMarketplaceSuggestion(
                    $tenantId,
                    'clients',
                    'Clients',
                    'Installez Clients pour rattacher vos articles et commandes a vos clients CRM.'
                ),
                $this->makeMarketplaceSuggestion(
                    $tenantId,
                    'invoice',
                    'Facturation',
                    'Installez Facturation pour transformer vos articles en devis et factures en quelques clics.'
                ),
            ])),
        ]);
    }

    public function articlesCreate()
    {
        return view('stock::articles.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'statuses' => config('stock.article_statuses', []),
        ]);
    }

    public function articlesStore(ArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->service->createArticle($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Article cree avec succes.',
                'redirect' => route('stock.articles.show', $article),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function articlesShow(Article $article)
    {
        $article->load('supplier');
        return view('stock::articles.show', compact('article'));
    }

    public function articlesEdit(Article $article)
    {
        return view('stock::articles.edit', [
            'article' => $article,
            'suppliers' => Supplier::orderBy('name')->get(),
            'statuses' => config('stock.article_statuses', []),
        ]);
    }

    public function articlesUpdate(ArticleRequest $request, Article $article): JsonResponse
    {
        try {
            $this->service->updateArticle($article, $request->validated());
            return response()->json(['success' => true, 'message' => 'Article mis a jour.', 'redirect' => route('stock.articles.show', $article)]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function articlesDestroy(Article $article): JsonResponse
    {
        $article->delete();
        return response()->json(['success' => true, 'message' => 'Article supprime.']);
    }

    public function articlesData(Request $request): JsonResponse
    {
        $query = Article::with('supplier')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')));
            // ->orderBy($request->string('sort_by', 'created_at')->toString(), $request->string('sort_dir', 'desc')->toString());

        $rows = $query->paginate($request->integer('per_page', config('stock.pagination.per_page')));
        return response()->json([
            'data' => $rows->items(),
            'current_page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
            'per_page' => $rows->perPage(),
            'total' => $rows->total(),
            'from' => $rows->firstItem(),
            'to' => $rows->lastItem(),
        ]);
    }

    public function articlesSearch(Request $request): JsonResponse
    {
        $items = Article::search($request->string('q')->toString())
            ->limit(15)
            ->get(['id', 'name', 'sku', 'sale_price', 'unit', 'stock_quantity']);
        return response()->json(['success' => true, 'data' => $items]);
    }

    public function articlesExportExcel()
    {
        return Excel::download(new ArticlesExport(), 'articles_' . date('Y-m-d') . '.xlsx');
    }

    public function articlesImport(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        Excel::import(new ArticlesImport(), $request->file('file'));
        return response()->json(['success' => true, 'message' => 'Import articles termine.']);
    }

    public function suppliersIndex()
    {
        return view('stock::suppliers.index');
    }

    public function suppliersCreate()
    {
        return view('stock::suppliers.create');
    }

    public function suppliersStore(SupplierRequest $request): JsonResponse
    {
        $supplier = $this->service->createSupplier($request->validated());
        return response()->json(['success' => true, 'message' => 'Fournisseur cree.', 'redirect' => route('stock.suppliers.show', $supplier)], 201);
    }

    public function suppliersShow(Supplier $supplier)
    {
        return view('stock::suppliers.show', compact('supplier'));
    }

    public function suppliersEdit(Supplier $supplier)
    {
        return view('stock::suppliers.edit', compact('supplier'));
    }

    public function suppliersUpdate(SupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->service->updateSupplier($supplier, $request->validated());
        return response()->json(['success' => true, 'message' => 'Fournisseur mis a jour.', 'redirect' => route('stock.suppliers.show', $supplier)]);
    }

    public function suppliersDestroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();
        return response()->json(['success' => true, 'message' => 'Fournisseur supprime.']);
    }

    public function suppliersData(Request $request): JsonResponse
    {
        $rows = Supplier::search($request->string('search')->toString())
            // ->orderBy($request->string('sort_by', 'created_at')->toString(), $request->string('sort_dir', 'desc')->toString())
            ->paginate($request->integer('per_page', config('stock.pagination.per_page')));

        return response()->json([
            'data' => $rows->items(),
            'current_page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
            'per_page' => $rows->perPage(),
            'total' => $rows->total(),
            'from' => $rows->firstItem(),
            'to' => $rows->lastItem(),
        ]);
    }

    public function suppliersExportExcel()
    {
        return Excel::download(new SuppliersExport(), 'fournisseurs_' . date('Y-m-d') . '.xlsx');
    }

    public function ordersIndex()
    {
        return view('stock::orders.index', [
            'statuses' => config('stock.order_statuses', []),
        ]);
    }

    public function ordersCreate()
    {
        return view('stock::orders.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'articles' => Article::where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku', 'sale_price', 'unit']),
            'statuses' => config('stock.order_statuses', []),
        ]);
    }

    public function ordersStore(OrderRequest $request): JsonResponse
    {
        $order = $this->service->createOrder($request->validated());
        return response()->json(['success' => true, 'message' => 'Commande creee.', 'redirect' => route('stock.orders.show', $order)], 201);
    }

    public function ordersShow(Order $order)
    {
        $order->load(['supplier', 'items.article']);
        return view('stock::orders.show', compact('order'));
    }

    public function ordersEdit(Order $order)
    {
        $order->load('items.article');
        return view('stock::orders.edit', [
            'order' => $order,
            'suppliers' => Supplier::orderBy('name')->get(),
            'articles' => Article::where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku', 'sale_price', 'unit']),
            'statuses' => config('stock.order_statuses', []),
        ]);
    }

    public function ordersUpdate(OrderRequest $request, Order $order): JsonResponse
    {
        $this->service->updateOrder($order, $request->validated());
        return response()->json(['success' => true, 'message' => 'Commande mise a jour.', 'redirect' => route('stock.orders.show', $order)]);
    }

    public function ordersDestroy(Order $order): JsonResponse
    {
        $order->delete();
        return response()->json(['success' => true, 'message' => 'Commande supprimee.']);
    }

    public function ordersData(Request $request): JsonResponse
    {
        $rows = Order::with('supplier')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            // ->orderBy($request->string('sort_by', 'created_at')->toString(), $request->string('sort_dir', 'desc')->toString())
            ->paginate($request->integer('per_page', config('stock.pagination.per_page')));

        return response()->json([
            'data' => $rows->items(),
            'current_page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
            'per_page' => $rows->perPage(),
            'total' => $rows->total(),
            'from' => $rows->firstItem(),
            'to' => $rows->lastItem(),
        ]);
    }

    public function ordersSearch(Request $request): JsonResponse
    {
        $rows = Order::search($request->string('q')->toString())
            ->with('items')
            ->limit(15)
            ->get(['id', 'number', 'status', 'total']);
        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function ordersDetail(Order $order): JsonResponse
    {
        $order->load('items.article');
        return response()->json(['success' => true, 'data' => $order]);
    }

    public function ordersReceive(Order $order): JsonResponse
    {
        $this->service->receiveOrder($order);
        return response()->json(['success' => true, 'message' => 'Commande marquee comme recue.']);
    }

    public function ordersExportExcel()
    {
        return Excel::download(new OrdersExport(), 'commandes_' . date('Y-m-d') . '.xlsx');
    }

    private function makeMarketplaceSuggestion(int $tenantId, string $slug, string $fallbackName, string $description): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }

        $extension = Extension::query()->where('slug', $slug)->first();
        if (!$extension) {
            return null;
        }

        $isActive = TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();

        if ($isActive) {
            return null;
        }

        return [
            'slug' => $slug,
            'name' => (string) ($extension->name ?: $fallbackName),
            'description' => $description,
            'url' => route('marketplace.show', ['slug' => $slug]),
            'icon' => (string) ($extension->icon ?: 'fas fa-puzzle-piece'),
        ];
    }
}
