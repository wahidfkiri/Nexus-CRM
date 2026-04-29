<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de livraison {{ $deliveryNote->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; color: #0f172a; margin: 0; }
        .wrap { padding: 34px 36px 40px; }
        .header-band { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: bold; color: #0f766e; }
        .subtle { color: #64748b; font-size: 9pt; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .grid td { width: 50%; border: 1px solid #e2e8f0; padding: 10px 12px; vertical-align: top; }
        .grid .label { font-size: 8pt; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .items { width: 100%; border-collapse: collapse; }
        .items th { background: #0f766e; color: #fff; padding: 8px; text-align: left; font-size: 8pt; text-transform: uppercase; }
        .items td { border: 1px solid #e2e8f0; padding: 8px; font-size: 9pt; }
        .right { text-align: right; }
        .note { margin-top: 16px; border: 1px solid #e2e8f0; border-left: 4px solid #0f766e; padding: 12px; border-radius: 6px; }
        .footer { margin-top: 20px; font-size: 8pt; color: #64748b; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header-band">
        <div class="title">{{ $deliveryNote->number }}</div>
        <div class="subtle">{{ $deliveryNote->type_label }} - statut {{ $deliveryNote->status_label }}</div>
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="label">Partenaire</div>
                <strong>{{ $deliveryNote->type === 'in' ? ($deliveryNote->supplier?->name ?: '—') : ($deliveryNote->client?->company_name ?: '—') }}</strong>
            </td>
            <td>
                <div class="label">Date du BL</div>
                <strong>{{ optional($deliveryNote->issue_date)->format('d/m/Y') ?: '—' }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Reference</div>
                <strong>{{ $deliveryNote->reference ?: '—' }}</strong>
            </td>
            <td>
                <div class="label">Commande liee</div>
                <strong>{{ $deliveryNote->order?->number ?: '—' }}</strong>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Article</th>
                <th>SKU</th>
                <th class="right">Quantite</th>
                <th>Unite</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveryNote->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->sku ?: ($item->article?->sku ?: '—') }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 4, ',', ' ') }}</td>
                    <td>{{ $item->unit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($deliveryNote->notes))
        <div class="note">
            <strong>Notes</strong><br>
            {{ $deliveryNote->notes }}
        </div>
    @endif

    <div class="footer">Genere le {{ now()->format('d/m/Y H:i') }}</div>
</div>
</body>
</html>