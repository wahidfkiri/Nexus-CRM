<?php

namespace Vendor\Stock\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Vendor\Stock\Models\Article;

class ArticlesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Article::query()->get([
            'id', 'sku', 'name', 'unit', 'purchase_price', 'sale_price', 'stock_quantity', 'min_stock', 'status',
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'SKU', 'Nom', 'Unite', 'Prix achat', 'Prix vente', 'Stock', 'Stock minimum', 'Statut'];
    }
}
