<?php

namespace Vendor\Stock\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Vendor\Stock\Models\Article;

class ArticlesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Article([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'sku' => $row['sku'] ?? null,
            'name' => $row['nom'] ?? ($row['name'] ?? null),
            'unit' => $row['unite'] ?? ($row['unit'] ?? 'piece'),
            'purchase_price' => (float)($row['prix_achat'] ?? 0),
            'sale_price' => (float)($row['prix_vente'] ?? ($row['sale_price'] ?? 0)),
            'stock_quantity' => (float)($row['stock'] ?? 0),
            'min_stock' => (float)($row['stock_minimum'] ?? 0),
            'status' => in_array(($row['statut'] ?? 'active'), ['active', 'inactive'], true) ? ($row['statut'] ?? 'active') : 'active',
        ]);
    }
}
