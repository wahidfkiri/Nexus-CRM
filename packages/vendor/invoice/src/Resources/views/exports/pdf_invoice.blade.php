<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #0f172a; background: #fff; }
        .wrap { padding: 40px; max-width: 800px; margin: 0 auto; }

        /* Header */
        .pdf-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
        .logo-area img { max-height: 60px; }
        .company-name { font-size: 18pt; font-weight: bold; color: #0f172a; }
        .company-details { font-size: 9pt; color: #64748b; line-height: 1.7; margin-top: 6px; }
        .inv-meta { text-align: right; }
        .inv-number-big { font-size: 20pt; font-weight: bold; color: #2563eb; }
        .inv-type { font-size: 11pt; color: #64748b; margin-bottom: 6px; }
        .inv-date { font-size: 9pt; color: #64748b; margin-top: 4px; line-height: 1.6; }

        /* Status badge */
        .status-pill {
            display: inline-block; padding: 3px 12px; border-radius: 999px;
            font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: .06em;
            margin-top: 6px;
        }
        .status-draft    { background: #f1f5f9; color: #64748b; }
        .status-sent     { background: #cffafe; color: #0891b2; }
        .status-paid     { background: #d1fae5; color: #059669; }
        .status-overdue  { background: #fee2e2; color: #dc2626; }
        .status-cancelled{ background: #f1f5f9; color: #94a3b8; }
        .status-partial  { background: #fef3c7; color: #d97706; }

        /* Addresses */
        .addresses { display: flex; gap: 32px; margin-bottom: 32px; background: #f8fafc; border-radius: 8px; padding: 20px 24px; }
        .addr-block { flex: 1; }
        .addr-label { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-bottom: 8px; }
        .addr-name  { font-size: 12pt; font-weight: bold; color: #0f172a; margin-bottom: 6px; }
        .addr-detail{ font-size: 9pt; color: #475569; line-height: 1.7; }

        /* Dates strip */
        .dates-strip { display: flex; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 24px; overflow: hidden; }
        .date-item { flex: 1; padding: 12px 16px; border-right: 1px solid #e2e8f0; }
        .date-item:last-child { border-right: none; }
        .date-lbl { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; margin-bottom: 4px; }
        .date-val { font-size: 10pt; font-weight: 600; color: #0f172a; }
        .date-val.overdue { color: #dc2626; }

        /* Items table */
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.items thead { background: #2563eb; }
        table.items thead th {
            padding: 10px 12px; text-align: left; font-size: 8pt;
            font-weight: bold; text-transform: uppercase; letter-spacing: .05em; color: #fff;
        }
        table.items thead th:last-child { text-align: right; }
        table.items tbody td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 9.5pt; color: #1e293b; vertical-align: top; }
        table.items tbody tr:nth-child(even) td { background: #f8fafc; }
        table.items td.right  { text-align: right; font-weight: 600; }
        table.items td.muted  { color: #94a3b8; font-size: 8.5pt; }
        table.items td.ref    { font-size: 8pt; color: #94a3b8; margin-top: 3px; }

        /* Totals */
        .totals-right { float: right; width: 260px; margin-bottom: 24px; }
        .tot-row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #e2e8f0; font-size: 9.5pt; }
        .tot-row.grand { font-size: 13pt; font-weight: bold; color: #2563eb; border-top: 2px solid #0f172a; border-bottom: none; padding-top: 12px; }
        .tot-row.disc  { color: #dc2626; }
        .tot-row.whold { color: #d97706; }
        .tot-row.due-box { background: #fee2e2; border-radius: 6px; padding: 10px 12px; margin-top: 10px; color: #dc2626; font-weight: bold; border: none; }

        /* Payments */
        .payments-section { clear: both; margin-top: 24px; }
        .section-title { font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; margin-bottom: 10px; }
        .payment-row { display: flex; justify-content: space-between; padding: 8px 12px; background: #f0fdf4; border-radius: 6px; margin-bottom: 6px; font-size: 9pt; }
        .payment-amount { font-weight: bold; color: #059669; }

        /* Notes */
        .notes-box { background: #f8fafc; border-left: 4px solid #2563eb; padding: 12px 16px; border-radius: 0 6px 6px 0; margin-top: 20px; font-size: 9pt; color: #475569; line-height: 1.7; }
        .terms-box  { margin-top: 14px; font-size: 8.5pt; color: #94a3b8; line-height: 1.7; }

        /* Bank details */
        .bank-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; margin-top: 16px; font-size: 9pt; }
        .bank-box .section-title { margin-bottom: 8px; }
        .bank-row { display: flex; gap: 8px; margin-bottom: 4px; }
        .bank-label { color: #94a3b8; min-width: 100px; }
        .bank-value { font-weight: 600; color: #0f172a; }

        /* Footer */
        .pdf-footer { margin-top: 40px; padding-top: 14px; border-top: 1px solid #e2e8f0; font-size: 8pt; color: #94a3b8; text-align: center; line-height: 1.7; }

        /* Watermark */
        .watermark {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%,-50%) rotate(-35deg);
            font-size: 80pt; font-weight: 900;
            color: rgba(220,38,38,.08); white-space: nowrap;
            pointer-events: none; z-index: 0; letter-spacing: .1em;
        }

        /* Progress */
        .progress { height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin-top: 6px; }
        .progress-fill { height: 100%; background: #059669; border-radius: 999px; }

        @page { margin: 0; }
    </style>
</head>
<body>

{{-- Watermark --}}
@if(in_array($invoice->status, ['draft','cancelled']))
<div class="watermark">{{ strtoupper(config("invoice.pdf.watermark.{$invoice->status}", '')) }}</div>
@endif

<div class="wrap">

    {{-- Header --}}
    <div class="pdf-header">
        <div class="logo-area">
            @if($invoice->tenant && $invoice->tenant->logo)
                <img src="{{ public_path($invoice->tenant->logo) }}" alt="Logo">
            @else
                <div class="company-name">{{ $invoice->tenant->name ?? config('app.name') }}</div>
            @endif
            <div class="company-details">
                {{ $invoice->tenant->address ?? '' }}<br>
                {{ $invoice->tenant->email ?? '' }}<br>
                @if($invoice->tenant->vat_number ?? false) TVA : {{ $invoice->tenant->vat_number }} @endif
            </div>
        </div>
        <div class="inv-meta">
            <div class="inv-type">FACTURE</div>
            <div class="inv-number-big">{{ $invoice->number }}</div>
            @if($invoice->reference)
            <div class="inv-date">Réf : {{ $invoice->reference }}</div>
            @endif
            <div><span class="status-pill status-{{ $invoice->status }}">{{ $invoice->status_label }}</span></div>
        </div>
    </div>

    {{-- Dates strip --}}
    <div class="dates-strip">
        <div class="date-item">
            <div class="date-lbl">Date d'émission</div>
            <div class="date-val">{{ $invoice->issue_date->format('d/m/Y') }}</div>
        </div>
        @if($invoice->due_date)
        <div class="date-item">
            <div class="date-lbl">Échéance</div>
            <div class="date-val {{ $invoice->is_overdue ? 'overdue' : '' }}">
                {{ $invoice->due_date->format('d/m/Y') }}
            </div>
        </div>
        @endif
        <div class="date-item">
            <div class="date-lbl">Devise</div>
            <div class="date-val">{{ $invoice->currency }} {{ $invoice->currency_symbol }}</div>
        </div>
        @if($invoice->payment_method)
        <div class="date-item">
            <div class="date-lbl">Mode paiement</div>
            <div class="date-val">{{ config("invoice.payment_methods.{$invoice->payment_method}") }}</div>
        </div>
        @endif
    </div>

    {{-- Addresses --}}
    <div class="addresses">
        <div class="addr-block">
            <div class="addr-label">Émetteur</div>
            <div class="addr-name">{{ $invoice->tenant->name ?? config('app.name') }}</div>
            <div class="addr-detail">
                {{ $invoice->tenant->address ?? '' }}<br>
                {{ $invoice->tenant->email ?? '' }}<br>
                {{ $invoice->tenant->phone ?? '' }}
            </div>
        </div>
        <div class="addr-block">
            <div class="addr-label">Facturé à</div>
            <div class="addr-name">{{ $invoice->client->company_name }}</div>
            <div class="addr-detail">
                {{ $invoice->client->contact_name }}<br>
                {{ $invoice->client->full_address }}<br>
                {{ $invoice->client->email }}
                @if($invoice->client->vat_number)
                <br>TVA : {{ $invoice->client->vat_number }}
                @endif
                @if($invoice->client->siret)
                <br>SIRET : {{ $invoice->client->siret }}
                @endif
            </div>
        </div>
    </div>

    {{-- Items table --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Description</th>
                <th style="width:70px;text-align:right">Qté</th>
                <th style="width:60px">Unité</th>
                <th style="width:100px;text-align:right">P.U. HT</th>
                <th style="width:80px;text-align:right">Remise</th>
                <th style="width:60px;text-align:right">TVA</th>
                <th style="width:100px;text-align:right">Total TTC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
            <tr>
                <td class="muted">{{ $i + 1 }}</td>
                <td>
                    {{ $item->description }}
                    @if($item->reference)
                    <div class="ref">Réf : {{ $item->reference }}</div>
                    @endif
                </td>
                <td class="right">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                <td class="muted">{{ $item->unit ?: '' }}</td>
                <td class="right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                <td class="right" style="color:#dc2626">
                    @if($item->discount_amount > 0)-{{ number_format($item->discount_amount, 2, ',', ' ') }}@else—@endif
                </td>
                <td class="right">{{ $item->tax_rate }}%</td>
                <td class="right">{{ number_format($item->total, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals-right">
        <div class="tot-row">
            <span>Sous-total HT</span>
            <strong>{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong>
        </div>
        @if($invoice->discount_amount > 0)
        <div class="tot-row disc">
            <span>Remise</span>
            <strong>-{{ number_format($invoice->discount_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong>
        </div>
        @endif
        <div class="tot-row">
            <span>TVA ({{ $invoice->tax_rate }}%)</span>
            <strong>{{ number_format($invoice->tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong>
        </div>
        @if($invoice->withholding_tax_rate > 0)
        <div class="tot-row whold">
            <span>Retenue à la source ({{ $invoice->withholding_tax_rate }}%)</span>
            <strong>-{{ number_format($invoice->withholding_tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong>
        </div>
        @endif
        <div class="tot-row grand">
            <span>Total TTC</span>
            <strong>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong>
        </div>
        @if($invoice->amount_paid > 0)
        <div class="tot-row" style="color:#059669">
            <span>Payé</span>
            <strong>{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong>
        </div>
        @endif
        @if($invoice->amount_due > 0)
        <div class="tot-row due-box">
            <span>💰 Reste à payer</span>
            <strong>{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong>
        </div>
        @endif

        @if($invoice->amount_paid > 0 && $invoice->total > 0)
        <div style="margin-top:10px;font-size:8pt;color:#94a3b8;text-align:right">Règlement : {{ $invoice->progress_percent }}%</div>
        <div class="progress"><div class="progress-fill" style="width:{{ $invoice->progress_percent }}%"></div></div>
        @endif
    </div>

    <div style="clear:both"></div>

    {{-- Paiements enregistrés --}}
    @if($invoice->payments->isNotEmpty())
    <div class="payments-section">
        <div class="section-title">Paiements reçus</div>
        @foreach($invoice->payments as $p)
        <div class="payment-row">
            <span>{{ $p->payment_date->format('d/m/Y') }} — {{ $p->method_label }}@if($p->reference) ({{ $p->reference }})@endif</span>
            <span class="payment-amount">{{ number_format($p->amount, 2, ',', ' ') }} {{ $p->currency }}</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Notes --}}
    @if($invoice->notes)
    <div class="notes-box">
        <div class="section-title" style="margin-bottom:6px">Notes</div>
        {{ $invoice->notes }}
    </div>
    @endif

    @if($invoice->terms)
    <div class="terms-box">
        <div class="section-title" style="margin-bottom:6px">Conditions</div>
        {{ $invoice->terms }}
    </div>
    @endif

    {{-- Pied de page --}}
    <div class="pdf-footer">
        @if($invoice->footer){{ $invoice->footer }}<br>@endif
        {{ $invoice->tenant->name ?? config('app.name') }}
        @if($invoice->tenant->email ?? false) — {{ $invoice->tenant->email }}@endif
        @if($invoice->tenant->phone ?? false) — {{ $invoice->tenant->phone }}@endif
        <br>
        Facture générée le {{ now()->format('d/m/Y à H:i') }}
    </div>

</div>
</body>
</html>
