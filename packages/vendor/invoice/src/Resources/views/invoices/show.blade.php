@extends('invoice::layouts.invoice')

@section('title', 'Facture ' . $invoice->number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <span class="title-icon">📄</span>
            Facture {{ $invoice->number }}
        </h1>
        <p class="page-subtitle">Créée le {{ $invoice->created_at->format('d/m/Y') }}</p>
    </div>
    <div class="page-actions no-print">
        @if(!in_array($invoice->status, ['paid','cancelled']))
        <button class="btn btn-success" onclick="sendInvoice({{ $invoice->id }})">📤 Envoyer</button>
        @endif

        <div class="btn-dropdown-wrap">
            <button class="btn btn-outline" data-dropdown-toggle="inv-actions-menu">Actions ▾</button>
            <div class="btn-dropdown" id="inv-actions-menu">
                @if(!in_array($invoice->status, ['paid','cancelled']))
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn-dropdown-item">✏️ Modifier</a>
                @endif
                <a href="#" class="btn-dropdown-item" onclick="duplicateInvoice({{ $invoice->id }})">📋 Dupliquer</a>
                <div class="btn-dropdown-divider"></div>
                <a href="{{ route('invoices.export.csv') }}" class="btn-dropdown-item">📊 Export CSV</a>
                <div class="btn-dropdown-divider"></div>
                @if($invoice->status !== 'paid')
                <a href="#" class="btn-dropdown-item danger" onclick="deleteInvoice({{ $invoice->id }})">🗑 Supprimer</a>
                @endif
            </div>
        </div>

        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-primary" target="_blank">
            📄 Télécharger PDF
        </a>
    </div>
</div>

<div class="invoice-show-grid">

    {{-- COLONNE PRINCIPALE --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Header card --}}
        <div class="invoice-header-card">
            <div class="invoice-meta-bar">
                <div>
                    <div class="invoice-number-display">{{ $invoice->number }}</div>
                    @if($invoice->reference)
                    <div style="font-size:13px;color:var(--c-ink-40);margin-top:4px">Réf : {{ $invoice->reference }}</div>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:12px">
                    <span class="currency-badge" style="font-size:13px;padding:5px 10px">
                        {{ $invoice->currency }} {{ $invoice->currency_symbol }}
                    </span>
                    <span class="status-badge badge-{{ $invoice->status }}" style="font-size:13px;padding:5px 14px">
                        {{ $invoice->status_label }}
                    </span>
                    @if($invoice->is_overdue)
                    <span class="status-badge badge-overdue">⚠ En retard</span>
                    @endif
                </div>
            </div>

            <div class="invoice-dates-grid">
                <div class="invoice-date-item">
                    <div class="invoice-date-label">Date d'émission</div>
                    <div class="invoice-date-value">{{ $invoice->issue_date->format('d/m/Y') }}</div>
                </div>
                <div class="invoice-date-item">
                    <div class="invoice-date-label">Échéance</div>
                    <div class="invoice-date-value {{ $invoice->is_overdue ? 'overdue' : '' }}">
                        {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
                        @if($invoice->is_overdue)
                        <span style="font-size:11px"> ({{ abs($invoice->due_date->diffInDays(now())) }}j de retard)</span>
                        @endif
                    </div>
                </div>
                <div class="invoice-date-item">
                    <div class="invoice-date-label">Conditions</div>
                    <div class="invoice-date-value">{{ config("invoice.payment_terms.{$invoice->payment_terms}", $invoice->payment_terms . ' jours') }}</div>
                </div>
                @if($invoice->payment_method)
                <div class="invoice-date-item">
                    <div class="invoice-date-label">Mode de paiement</div>
                    <div class="invoice-date-value">{{ config("invoice.payment_methods.{$invoice->payment_method}", $invoice->payment_method) }}</div>
                </div>
                @endif
                @if($invoice->payment_date)
                <div class="invoice-date-item">
                    <div class="invoice-date-label">Date de paiement</div>
                    <div class="invoice-date-value" style="color:var(--c-success)">{{ $invoice->payment_date->format('d/m/Y') }}</div>
                </div>
                @endif
                @if($invoice->sent_at)
                <div class="invoice-date-item">
                    <div class="invoice-date-label">Envoyée le</div>
                    <div class="invoice-date-value">{{ $invoice->sent_at->format('d/m/Y H:i') }}</div>
                </div>
                @endif
            </div>

            {{-- Partie & Destinataire --}}
            <div class="client-info-grid">
                <div>
                    <div class="info-section-title">Émetteur</div>
                    <div class="client-card-mini">
                        <div class="client-avatar" style="background:var(--c-ink-02);color:var(--c-ink)">
                            {{ strtoupper(substr($invoice->tenant->name ?? 'E', 0, 2)) }}
                        </div>
                        <div>
                            <div class="client-name">{{ $invoice->tenant->name ?? config('app.name') }}</div>
                            <div class="client-detail">
                                {{ $invoice->tenant->email ?? '' }}<br>
                                {{ $invoice->tenant->address ?? '' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="info-section-title">Facturé à</div>
                    <div class="client-card-mini">
                        <div class="client-avatar">{{ $invoice->client->initials }}</div>
                        <div>
                            <div class="client-name">{{ $invoice->client->company_name }}</div>
                            <div class="client-detail">
                                {{ $invoice->client->contact_name }}<br>
                                {{ $invoice->client->email }}<br>
                                {{ $invoice->client->full_address }}
                                @if($invoice->client->vat_number)
                                <br>TVA : {{ $invoice->client->vat_number }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lignes --}}
        <div class="inv-card">
            <div class="inv-card-header">
                <span class="inv-card-title">📦 Lignes de facture</span>
            </div>
            <div class="table-responsive">
                <table class="show-items">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Description</th>
                            <th style="width:80px;text-align:right">Qté</th>
                            <th style="width:80px;text-align:right">Unité</th>
                            <th style="width:110px;text-align:right">Prix unit. HT</th>
                            <th style="width:100px;text-align:right">Remise</th>
                            <th style="width:80px;text-align:right">TVA</th>
                            <th style="width:120px;text-align:right">Total TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $i => $item)
                        <tr>
                            <td style="color:var(--c-ink-40);font-size:12px">{{ $i + 1 }}</td>
                            <td>
                                <div class="item-desc-cell">{{ $item->description }}</div>
                                @if($item->reference)
                                <div class="item-ref">Réf : {{ $item->reference }}</div>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                            <td class="text-right" style="color:var(--c-ink-40)">{{ $item->unit ?: '—' }}</td>
                            <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                            <td class="text-right" style="color:var(--c-danger)">
                                @if($item->discount_amount > 0)
                                    -{{ number_format($item->discount_amount, 2, ',', ' ') }}
                                @else —
                                @endif
                            </td>
                            <td class="text-right">{{ $item->tax_rate }}%</td>
                            <td class="text-right">{{ number_format($item->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totaux intégrés --}}
            <div style="display:flex;justify-content:flex-end;padding:0 20px 20px">
                <div style="width:280px">
                    <div class="totals-row">
                        <span class="totals-label">Sous-total HT</span>
                        <span class="totals-value">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
                    </div>
                    @if($invoice->discount_amount > 0)
                    <div class="totals-row discount">
                        <span class="totals-label">Remise</span>
                        <span class="totals-value">-{{ number_format($invoice->discount_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
                    </div>
                    @endif
                    <div class="totals-row tax">
                        <span class="totals-label">TVA ({{ $invoice->tax_rate }}%)</span>
                        <span class="totals-value">{{ number_format($invoice->tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
                    </div>
                    @if($invoice->withholding_tax_rate > 0)
                    <div class="totals-row withholding" style="color:var(--c-warning)">
                        <span class="totals-label">Retenue à la source ({{ $invoice->withholding_tax_rate }}%)</span>
                        <span class="totals-value">-{{ number_format($invoice->withholding_tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
                    </div>
                    @endif
                    <div class="totals-row grand-total">
                        <span class="totals-label">Total TTC</span>
                        <span class="totals-value">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
                    </div>
                    @if($invoice->amount_paid > 0)
                    <div class="totals-row" style="color:var(--c-success)">
                        <span class="totals-label">Montant payé</span>
                        <span class="totals-value">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
                    </div>
                    @endif
                    @if($invoice->amount_due > 0)
                    <div class="totals-row due-amount">
                        <span class="totals-label">💰 Reste à payer</span>
                        <span class="totals-value">{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
                    </div>
                    @endif
                </div>
            </div>

            @if($invoice->notes)
            <div style="padding:16px 20px;border-top:1px solid var(--c-ink-05)">
                <div class="info-section-title">Notes</div>
                <p style="font-size:13.5px;color:var(--c-ink-60);margin:6px 0 0;line-height:1.7">{{ $invoice->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Paiements --}}
        @if($invoice->payments->count() > 0 || !in_array($invoice->status, ['paid','cancelled']))
        <div class="inv-card">
            <div class="inv-card-header">
                <span class="inv-card-title">💳 Paiements</span>
                @if(!in_array($invoice->status, ['paid','cancelled']))
                <button class="btn btn-success btn-sm no-print" data-open-payment>+ Enregistrer un paiement</button>
                @endif
            </div>
            <div class="inv-card-body">
                @if($invoice->payments->isEmpty())
                <p style="color:var(--c-ink-40);font-size:13px;text-align:center;padding:20px 0">
                    Aucun paiement enregistré pour cette facture.
                </p>
                @else
                <div class="payment-list">
                    @foreach($invoice->payments as $payment)
                    <div class="payment-item">
                        <div class="payment-icon">💳</div>
                        <div class="payment-body">
                            <div class="payment-amount">
                                {{ number_format($payment->amount, 2, ',', ' ') }} {{ $payment->currency }}
                            </div>
                            <div class="payment-meta">
                                {{ $payment->payment_date->format('d/m/Y') }} —
                                {{ $payment->method_label }}
                                @if($payment->reference) — Réf : {{ $payment->reference }} @endif
                            </div>
                        </div>
                        @if(!$invoice->is_paid)
                        <button class="payment-delete-btn no-print"
                                onclick="deletePayment({{ $payment->id }})" title="Supprimer ce paiement">🗑</button>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Barre de progression --}}
                @if($invoice->total > 0)
                <div class="payment-progress" style="margin-top:16px">
                    <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--c-ink-40);margin-bottom:6px">
                        <span>Progression du paiement</span>
                        <span>{{ $invoice->progress_percent }}%</span>
                    </div>
                    <div class="progress-bar" style="height:8px">
                        <div class="progress-fill" style="width:{{ $invoice->progress_percent }}%"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- SIDEBAR --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Devis source --}}
        @if($invoice->quote)
        <div class="inv-card">
            <div class="inv-card-header"><span class="inv-card-title">📝 Devis source</span></div>
            <div class="inv-card-body">
                <a href="{{ route('invoices.quotes.show', $invoice->quote) }}" style="color:var(--c-accent);font-weight:600">
                    {{ $invoice->quote->number }}
                </a>
                <div style="font-size:12px;color:var(--c-ink-40);margin-top:4px">
                    Émis le {{ $invoice->quote->issue_date->format('d/m/Y') }}
                </div>
            </div>
        </div>
        @endif

        {{-- Quick stats --}}
        <div class="inv-card">
            <div class="inv-card-header"><span class="inv-card-title">📊 Résumé</span></div>
            <div class="inv-card-body" style="display:flex;flex-direction:column;gap:12px">
                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span style="color:var(--c-ink-40)">Total TTC</span>
                    <strong>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span style="color:var(--c-ink-40)">Payé</span>
                    <strong style="color:var(--c-success)">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span style="color:var(--c-ink-40)">Reste dû</span>
                    <strong style="color:{{ $invoice->amount_due > 0 ? 'var(--c-danger)' : 'var(--c-success)' }}">
                        {{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $invoice->currency_symbol }}
                    </strong>
                </div>
                <div class="progress-bar" style="height:8px">
                    <div class="progress-fill" style="width:{{ $invoice->progress_percent }}%"></div>
                </div>
            </div>
        </div>

        {{-- Activité --}}
        <div class="inv-card">
            <div class="inv-card-header"><span class="inv-card-title">📅 Activité</span></div>
            <div class="inv-card-body">
                <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;color:var(--c-ink-60)">
                    <div>📝 Créée le {{ $invoice->created_at->format('d/m/Y H:i') }}</div>
                    @if($invoice->sent_at)
                    <div>📤 Envoyée le {{ $invoice->sent_at->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($invoice->viewed_at)
                    <div>👁 Vue le {{ $invoice->viewed_at->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($invoice->payment_date)
                    <div style="color:var(--c-success)">✅ Payée le {{ $invoice->payment_date->format('d/m/Y') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Créée par --}}
        <div class="inv-card">
            <div class="inv-card-header"><span class="inv-card-title">👤 Créée par</span></div>
            <div class="inv-card-body">
                <div style="font-size:13px;color:var(--c-ink)">{{ $invoice->user->name ?? '—' }}</div>
                <div style="font-size:12px;color:var(--c-ink-40)">{{ $invoice->user->email ?? '' }}</div>
            </div>
        </div>
    </div>

</div>

{{-- Modal paiement --}}
<div class="modal-overlay" id="payment-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">💳 Enregistrer un paiement</h3>
            <button class="modal-close">×</button>
        </div>
        <div class="modal-body">
            <form id="payment-form" enctype="multipart/form-data">
                @csrf
                <div class="form-row col-2">
                    <div class="form-group">
                        <label>Montant <span class="required">*</span></label>
                        <input type="number" name="amount" class="form-control"
                               value="{{ $invoice->amount_due }}" min="0.01" step="any" required>
                    </div>
                    <div class="form-group">
                        <label>Devise <span class="required">*</span></label>
                        <select name="currency" class="form-select">
                            @foreach(config('invoice.currencies') as $code => $cfg)
                                <option value="{{ $code }}" {{ $code === $invoice->currency ? 'selected' : '' }}>
                                    {{ $code }} {{ $cfg['symbol'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row col-2">
                    <div class="form-group">
                        <label>Date de paiement <span class="required">*</span></label>
                        <input type="date" name="payment_date" class="form-control"
                               value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Mode de paiement <span class="required">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            @foreach(config('invoice.payment_methods') as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row col-2">
                    <div class="form-group">
                        <label>Référence</label>
                        <input type="text" name="reference" class="form-control" placeholder="N° chèque, virement…">
                    </div>
                    <div class="form-group">
                        <label>Banque</label>
                        <input type="text" name="bank_name" class="form-control" placeholder="Nom de la banque">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Justificatif (PDF, image)</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline modal-close-btn">Annuler</button>
            <button type="submit" form="payment-form" class="btn btn-success">💳 Enregistrer le paiement</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const INVOICE_CURRENCIES = @json(config('invoice.currencies'));

    document.addEventListener('DOMContentLoaded', () => {
        PaymentModal.init({ invoiceId: {{ $invoice->id }} });

        document.querySelector('.modal-close-btn')?.addEventListener('click', () => PaymentModal.close());
    });

    async function sendInvoice(id) {
        if (!confirm('Marquer cette facture comme envoyée ?')) return;
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const res  = await fetch(`/invoices/${id}/send`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'} });
        const json = await res.json();
        json.success ? (Toast.success('Envoyée', json.message), setTimeout(()=>location.reload(),1000)) : Toast.error('Erreur', json.message);
    }

    async function duplicateInvoice(id) {
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const res  = await fetch(`/invoices/${id}/duplicate`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'} });
        const json = await res.json();
        json.success ? (Toast.success('Dupliquée', json.message), setTimeout(()=>window.location.href=json.redirect,1000)) : Toast.error('Erreur', json.message);
    }

    async function deleteInvoice(id) {
        if (!confirm('Supprimer définitivement cette facture ?')) return;
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const res  = await fetch(`/invoices/${id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'} });
        const json = await res.json();
        json.success ? (Toast.success('Supprimée', json.message), setTimeout(()=>window.location.href='/invoices',1200)) : Toast.error('Erreur', json.message);
    }

    async function deletePayment(id) {
        if (!confirm('Supprimer ce paiement ?')) return;
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const res  = await fetch(`/invoices/payments/${id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'} });
        const json = await res.json();
        json.success ? (Toast.success('Supprimé', json.message), setTimeout(()=>location.reload(),1000)) : Toast.error('Erreur', json.message);
    }
</script>
@endpush
