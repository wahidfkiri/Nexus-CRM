<?php

namespace Vendor\Extensions\Exports;

use Vendor\Extensions\Models\Extension;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExtensionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Extension::withCount(['tenantExtensions as installs', 'activeTenants as active_installs'])
            ->orderBy('sort_order')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Slug', 'Nom', 'Catégorie', 'Tarification', 'Prix',
            'Statut', 'Vedette', 'Officiel', 'Installations', 'Actives',
            'Note', 'Version', 'Éditeur', 'Créée le',
        ];
    }

    public function map($ext): array
    {
        return [
            $ext->id,
            $ext->slug,
            $ext->name,
            $ext->category_label,
            config("extensions.pricing_types.{$ext->pricing_type}", $ext->pricing_type),
            $ext->pricing_type === 'free' ? 'Gratuit' : number_format($ext->price, 2) . ' ' . $ext->currency,
            $ext->status_label,
            $ext->is_featured ? 'Oui' : 'Non',
            $ext->is_official ? 'Oui' : 'Non',
            $ext->installs ?? 0,
            $ext->active_installs ?? 0,
            $ext->rating ?: '—',
            $ext->version,
            $ext->developer_name ?? '—',
            $ext->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF7C3AED']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}