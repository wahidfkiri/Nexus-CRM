<?php

namespace Vendor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class Article extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $table = 'stock_articles';

    protected $fillable = [
        'tenant_id', 'user_id', 'supplier_id', 'sku', 'name', 'description', 'unit',
        'purchase_price', 'sale_price', 'stock_quantity', 'min_stock', 'status',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:4',
        'sale_price' => 'decimal:4',
        'stock_quantity' => 'decimal:4',
        'min_stock' => 'decimal:4',
    ];

    protected $appends = ['is_low_stock'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'article_id');
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->stock_quantity <= (float) $this->min_stock;
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
