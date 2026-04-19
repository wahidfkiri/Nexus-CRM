<?php

namespace Vendor\Stock\Services;

use Illuminate\Support\Facades\DB;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\OrderItem;
use Vendor\Stock\Models\Supplier;
use Vendor\Stock\Repositories\StockRepository;

class StockService
{
    public function __construct(protected ?StockRepository $repo = null)
    {
        $this->repo = $this->repo ?: new StockRepository();
    }

    public function stats(): array
    {
        return [
            'articles_total' => Article::count(),
            'articles_low_stock' => Article::whereColumn('stock_quantity', '<=', 'min_stock')->count(),
            'suppliers_total' => Supplier::count(),
            'orders_total' => Order::count(),
            'orders_draft' => Order::where('status', 'draft')->count(),
            'orders_received' => Order::where('status', 'received')->count(),
        ];
    }

    public function createArticle(array $data): Article
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id'] = auth()->id();
        return Article::create($data);
    }

    public function updateArticle(Article $article, array $data): Article
    {
        $article->update($data);
        return $article->fresh('supplier');
    }

    public function createSupplier(array $data): Supplier
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id'] = auth()->id();
        return Supplier::create($data);
    }

    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier->fresh();
    }

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'supplier_id' => $data['supplier_id'],
                'number' => $this->generateOrderNumber(),
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncOrderItems($order, $data['items'] ?? []);
            $this->recalculateOrder($order);
            return $order->fresh(['supplier', 'items.article']);
        });
    }

    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'supplier_id' => $data['supplier_id'],
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'] ?? $order->status,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'expected_date' => $data['expected_date'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncOrderItems($order, $data['items'] ?? []);
            $this->recalculateOrder($order);
            return $order->fresh(['supplier', 'items.article']);
        });
    }

    public function receiveOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->article_id) {
                    Article::where('id', $item->article_id)->increment('stock_quantity', (float) $item->quantity);
                }
            }

            $order->update([
                'status' => 'received',
                'received_date' => now()->toDateString(),
            ]);

            return $order->fresh(['supplier', 'items.article']);
        });
    }

    public function syncOrderItems(Order $order, array $items): void
    {
        $order->items()->delete();
        foreach ($items as $index => $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            OrderItem::create([
                'order_id' => $order->id,
                'article_id' => $item['article_id'] ?? null,
                'position' => $index,
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? 'piece',
                'unit_price' => $unitPrice,
                'total' => $quantity * $unitPrice,
            ]);
        }
    }

    public function recalculateOrder(Order $order): void
    {
        $subtotal = (float) $order->items()->sum(DB::raw('quantity * unit_price'));
        $taxAmount = $subtotal * ((float) $order->tax_rate / 100);
        $order->updateQuietly([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]);
    }

    public function generateOrderNumber(): string
    {
        $tenantId = auth()->user()->tenant_id;
        $prefix = 'CMD';
        $year = now()->year;
        $last = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('%s-%s-%04d', $prefix, $year, $last + 1);
    }
}
