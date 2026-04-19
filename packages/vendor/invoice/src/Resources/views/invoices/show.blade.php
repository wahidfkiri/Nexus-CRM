@extends('invoice::layouts.invoice')

@section('title', 'Facture ' . $invoice->number)

@section('breadcrumb')
  <a href="{{ route('invoices.index') }}">Factures</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $invoice->number }}</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    @php
      $colors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706'];
      $color  = $colors[ord($invoice->number[0] ?? 'F') % count($colors)];
    @endphp
    <div style="width:56px;height:56px;border-radius:var(--r-md);background:{{ $color }};color:#fff;display:flex;align-items:center;justify-content:center;font-family:var(--ff-display);font-size:20px;font-weight:700;flex-shrink:0;">
      <i class="fas fa-file-invoice" style="font-size:22px;"></i>
    </div>
    <div>
      <h1 style="margin-bottom:6px;">{{ $invoice->number }}</h1>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="badge badge-{{ $invoice->status }}">
          <span class="badge-dot" style="background:currentColor"></span>
          {{ $invoice->status_label }}
        </span>
        @if($invoice->is_overdue)
          <span class="badge" style="background:var(--c-danger-lt);color:var(--c-danger)">
            <i class="fas fa-exclamation-triangle"></i> En retard
          </span>
        @endif
        <span style="font-size:12px;color:var(--c-ink-40)">
          <i class="fas fa-calendar" style="margin-right:4px;"></i>
          Émise le {{ $invoice->issue_date->format('d/m/Y') }}
        </span>
        <span style="font-size:12px;color:var(--c-ink-40)">
          <i class="fas fa-money-bill" style="margin-right:4px;"></i>
          {{ $invoice->currency }} {{ $invoice->currency_symbol }}
        </span>
      </div>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-secondary" target="_blank">
      <i class="fas fa-file-pdf"></i> PDF
    </a>
    @if(!in_array($invoice->status, ['paid','cancelled']))
      <button class="btn btn-secondary" onclick="sendInvoice({{ $invoice->id }})">
        <i class="fas fa-paper-plane"></i> Marquer envoyée
      </button>
    @endif
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-ellipsis"></i>
      </button>
      <div class="dropdown-menu">
        <a href="#" class="dropdown-item" onclick="duplicateInvoice({{ $invoice->id }})">
          <i class="fas fa-copy"></i> Dupliquer
        </a>
        @if(!in_array($invoice->status, ['paid','cancelled']))
          <a href="{{ route('invoices.edit', $invoice) }}" class="dropdown-item">
            <i class="fas fa-pen"></i> Modifier
          </a>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item danger" onclick="deleteInvoice({{ $invoice->id }})">
            <i class="fas fa-trash"></i> Supprimer
          </button>
        @endif
      </div>
    </div>
    @if(!in_array($invoice->status, ['paid','cancelled']))
      <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-primary">
        <i class="fas fa-pen"></i> Modifier
      </a>
    @endif
  </div>
</div>

{{-- KPIs --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-receipt"></i></div>
    <div class="stat-body">
      <div class="stat-value">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</div>
      <div class="stat-label">Total TTC</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body">
      <div class="stat-value">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</div>
      <div class="stat-label">Payé</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:{{ $invoice->amount_due > 0 ? 'var(--c-danger-lt)' : 'var(--c-success-lt)' }};color:{{ $invoice->amount_due > 0 ? 'var(--c-danger)' : 'var(--c-success)' }}">
      <i class="fas fa-{{ $invoice->amount_due > 0 ? 'clock-rotate-left' : 'check-double' }}"></i>
    </div>
    <div class="stat-body">
      <div class="stat-value" style="color:{{ $invoice->amount_due > 0 ? 'var(--c-danger)' : 'var(--c-success)' }}">
        {{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $invoice->currency_symbol }}
      </div>
      <div class="stat-label">Reste dû</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-calendar-days"></i></div>
    <div class="stat-body">
      <div class="stat-value" style="font-size:16px;{{ $invoice->is_overdue ? 'color:var(--c-danger)' : '' }}">
        {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
      </div>
      <div class="stat-label">Échéance</div>
      @if($invoice->is_overdue)
        <span class="stat-trend down"><i class="fas fa-exclamation"></i> {{ abs($invoice->due_date->diffInDays(now())) }}j de retard</span>
      @endif
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">

  {{-- COLONNE PRINCIPALE --}}
  <div class="col-8" style="padding:0 12px 0 0;">

    {{-- Infos générales --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-circle-info"></i>
        <h3>Informations de la facture</h3>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
        <div style="padding:20px;border-right:1px solid var(--c-ink-05);">
          <div style="font-size:11px;font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.07em;color:var(--c-ink-40);margin-bottom:10px;">Émetteur</div>
          <div style="font-weight:var(--fw-semi);font-size:14px;margin-bottom:4px;">{{ $invoice->tenant->name ?? config('app.name') }}</div>
          <div style="font-size:12.5px;color:var(--c-ink-40);line-height:1.7;">
            {{ $invoice->tenant->email ?? '' }}<br>
            {{ $invoice->tenant->address ?? '' }}
          </div>
        </div>
        <div style="padding:20px;">
          <div style="font-size:11px;font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.07em;color:var(--c-ink-40);margin-bottom:10px;">Facturé à</div>
          <div style="display:flex;gap:10px;align-items:flex-start;">
            @php $initials = strtoupper(substr($invoice->client->company_name ?? 'C', 0, 2)); @endphp
            <div class="client-avatar-sm" style="width:38px;height:38px;font-size:14px;">{{ $initials }}</div>
            <div>
              <div style="font-weight:var(--fw-semi);font-size:14px;margin-bottom:3px;">{{ $invoice->client->company_name }}</div>
              <div style="font-size:12.5px;color:var(--c-ink-40);line-height:1.7;">
                {{ $invoice->client->contact_name }}<br>
                {{ $invoice->client->email }}<br>
                {{ $invoice->client->full_address }}
              </div>
            </div>
          </div>
        </div>
      </div>
      {{-- Dates strip --}}
      <div style="display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--c-ink-05);">
        <div style="padding:14px 20px;border-right:1px solid var(--c-ink-05);">
          <div style="font-size:11px;color:var(--c-ink-40);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em;">Date émission</div>
          <div style="font-weight:var(--fw-semi);">{{ $invoice->issue_date->format('d/m/Y') }}</div>
        </div>
        <div style="padding:14px 20px;border-right:1px solid var(--c-ink-05);">
          <div style="font-size:11px;color:var(--c-ink-40);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em;">Échéance</div>
          <div style="font-weight:var(--fw-semi);{{ $invoice->is_overdue ? 'color:var(--c-danger)' : '' }}">
            {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
          </div>
        </div>
        <div style="padding:14px 20px;border-right:1px solid var(--c-ink-05);">
          <div style="font-size:11px;color:var(--c-ink-40);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em;">Paiement</div>
          <div style="font-weight:var(--fw-semi);">{{ config("invoice.payment_terms.{$invoice->payment_terms}", $invoice->payment_terms.'j') }}</div>
        </div>
        <div style="padding:14px 20px;">
          <div style="font-size:11px;color:var(--c-ink-40);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em;">Mode</div>
          <div style="font-weight:var(--fw-semi);">{{ config("invoice.payment_methods.{$invoice->payment_method}", $invoice->payment_method ?? '—') }}</div>
        </div>
      </div>
    </div>

    {{-- Lignes --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-list"></i>
        <h3>Lignes de facture</h3>
      </div>
      <div style="overflow-x:auto;">
        <table class="crm-table">
          <thead>
            <tr>
              <th style="width:30px">#</th>
              <th>Description</th>
              <th style="text-align:right;width:80px">Qté</th>
              <th style="text-align:right;width:80px">Unité</th>
              <th style="text-align:right;width:110px">P.U. HT</th>
              <th style="text-align:right;width:100px">Remise</th>
              <th style="text-align:right;width:80px">TVA</th>
              <th style="text-align:right;width:120px">Total TTC</th>
            </tr>
          </thead>
          <tbody>
            @foreach($invoice->items as $i => $item)
            <tr>
              <td style="color:var(--c-ink-40);font-size:12px;">{{ $i + 1 }}</td>
              <td>
                <div style="font-weight:var(--fw-medium);">{{ $item->description }}</div>
                @if($item->reference)
                  <div style="font-size:11.5px;color:var(--c-ink-40);">Réf : {{ $item->reference }}</div>
                @endif
              </td>
              <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
              <td class="text-right" style="color:var(--c-ink-40);">{{ $item->unit ?: '—' }}</td>
              <td class="text-right font-mono">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
              <td class="text-right" style="color:var(--c-danger);">
                @if($item->discount_amount > 0) -{{ number_format($item->discount_amount, 2, ',', ' ') }} @else — @endif
              </td>
              <td class="text-right">{{ $item->tax_rate }}%</td>
              <td class="text-right fw-semi font-mono">{{ number_format($item->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      {{-- Totaux --}}
      <div style="display:flex;justify-content:flex-end;padding:20px;">
        <div style="width:280px;">
          <div class="totals-panel">
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
            <div class="totals-row">
              <span class="totals-label">TVA ({{ $invoice->tax_rate }}%)</span>
              <span class="totals-value">{{ number_format($invoice->tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @if($invoice->withholding_tax_rate > 0)
            <div class="totals-row" style="color:var(--c-warning);">
              <span class="totals-label">Retenue ({{ $invoice->withholding_tax_rate }}%)</span>
              <span class="totals-value">-{{ number_format($invoice->withholding_tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @endif
            <div class="totals-row grand-total">
              <span class="totals-label">Total TTC</span>
              <span class="totals-value">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @if($invoice->amount_paid > 0)
            <div class="totals-row" style="color:var(--c-success);">
              <span class="totals-label">Montant payé</span>
              <span class="totals-value">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @endif
            @if($invoice->amount_due > 0)
            <div class="totals-row due-amount">
              <span class="totals-label"><i class="fas fa-coins"></i> Reste à payer</span>
              <span class="totals-value">{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @endif
          </div>
          {{-- Progress bar --}}
          @if($invoice->total > 0)
          <div style="margin-top:16px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--c-ink-40);margin-bottom:6px;">
              <span>Règlement</span><span>{{ $invoice->progress_percent }}%</span>
            </div>
            <div class="progress-bar-wrap">
              <div class="progress-bar-fill" style="width:{{ $invoice->progress_percent }}%"></div>
            </div>
          </div>
          @endif
        </div>
      </div>

      @if($invoice->notes)
      <div style="padding:16px 20px;border-top:1px solid var(--c-ink-05);">
        <div style="font-size:11px;font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.05em;color:var(--c-ink-40);margin-bottom:6px;">Notes</div>
        <p style="font-size:13.5px;color:var(--c-ink-60);line-height:1.7;margin:0;">{{ $invoice->notes }}</p>
      </div>
      @endif
    </div>

    {{-- Paiements --}}
    <div class="info-card">
      <div class="info-card-header">
        <i class="fas fa-credit-card"></i>
        <h3>Paiements reçus</h3>
        @if(!in_array($invoice->status, ['paid','cancelled']))
          <button class="btn btn-sm btn-success" style="margin-left:auto;" data-modal-open="paymentModal">
            <i class="fas fa-plus"></i> Enregistrer un paiement
          </button>
        @endif
      </div>
      <div class="info-card-body">
        @if($invoice->payments->isEmpty())
          <div style="text-align:center;padding:24px;color:var(--c-ink-40);">
            <i class="fas fa-credit-card" style="font-size:24px;margin-bottom:10px;display:block;opacity:.3;"></i>
            Aucun paiement enregistré pour cette facture.
          </div>
        @else
          @foreach($invoice->payments as $payment)
          <div class="payment-item">
            <div class="payment-icon"><i class="fas fa-circle-check"></i></div>
            <div style="flex:1;">
              <div class="payment-amount">{{ number_format($payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</div>
              <div class="payment-meta">
                {{ $payment->payment_date->format('d/m/Y') }} —
                {{ $payment->method_label }}
                @if($payment->reference) · Réf : {{ $payment->reference }} @endif
                @if($payment->bank_name) · {{ $payment->bank_name }} @endif
              </div>
            </div>
            @if(!$invoice->is_paid)
              <button class="btn-icon danger btn-sm" onclick="deletePayment({{ $payment->id }})" title="Supprimer">
                <i class="fas fa-trash"></i>
              </button>
            @endif
          </div>
          @endforeach
        @endif
      </div>
    </div>

  </div>

  {{-- SIDEBAR --}}
  <div class="col-4" style="padding:0 0 0 12px;">

    {{-- Activité --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-clock-rotate-left"></i>
        <h3>Activité</h3>
      </div>
      <div class="info-card-body">
        <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;color:var(--c-ink-60);">
          <div><i class="fas fa-file-circle-plus" style="color:var(--c-accent);width:16px;"></i> Créée le {{ $invoice->created_at->format('d/m/Y H:i') }}</div>
          @if($invoice->sent_at)
            <div><i class="fas fa-paper-plane" style="color:var(--c-info);width:16px;"></i> Envoyée le {{ $invoice->sent_at->format('d/m/Y H:i') }}</div>
          @endif
          @if($invoice->viewed_at)
            <div><i class="fas fa-eye" style="color:var(--c-purple);width:16px;"></i> Vue le {{ $invoice->viewed_at->format('d/m/Y H:i') }}</div>
          @endif
          @if($invoice->payment_date)
            <div><i class="fas fa-circle-check" style="color:var(--c-success);width:16px;"></i> Payée le {{ $invoice->payment_date->format('d/m/Y') }}</div>
          @endif
        </div>
      </div>
    </div>

    {{-- Devis source --}}
    @if($invoice->quote)
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-file-signature"></i>
        <h3>Devis source</h3>
      </div>
      <div class="info-card-body">
        <a href="{{ route('invoices.quotes.show', $invoice->quote) }}" style="color:var(--c-accent);font-weight:var(--fw-semi);">
          {{ $invoice->quote->number }}
        </a>
        <div style="font-size:12px;color:var(--c-ink-40);margin-top:4px;">
          Émis le {{ $invoice->quote->issue_date->format('d/m/Y') }}
        </div>
      </div>
    </div>
    @endif

    {{-- Infos commerciales --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-chart-bar"></i>
        <h3>Informations</h3>
      </div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label">Référence</span>
          <span class="info-row-value">{{ $invoice->reference ?? '—' }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Créée par</span>
          <span class="info-row-value">{{ $invoice->user->name ?? '—' }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Rappels</span>
          <span class="info-row-value">{{ $invoice->reminder_count }} envoyé(s)</span>
        </div>
        @if($invoice->last_reminder_at)
        <div class="info-row">
          <span class="info-row-label">Dernier rappel</span>
          <span class="info-row-value">{{ $invoice->last_reminder_at->format('d/m/Y') }}</span>
        </div>
        @endif
      </div>
    </div>

    {{-- Actions rapides --}}
    <div class="info-card">
      <div class="info-card-header">
        <i class="fas fa-bolt"></i>
        <h3>Actions rapides</h3>
      </div>
      <div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;">
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn btn-secondary" style="justify-content:flex-start;">
          <i class="fas fa-file-pdf"></i> Télécharger PDF
        </a>
        @if(!in_array($invoice->status, ['paid','cancelled']))
          <button class="btn btn-secondary" style="justify-content:flex-start;" onclick="sendInvoice({{ $invoice->id }})">
            <i class="fas fa-paper-plane"></i> Marquer comme envoyée
          </button>
          <button class="btn btn-secondary" style="justify-content:flex-start;" data-modal-open="paymentModal">
            <i class="fas fa-credit-card"></i> Enregistrer un paiement
          </button>
          <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary" style="justify-content:flex-start;">
            <i class="fas fa-pen"></i> Modifier
          </a>
        @endif
        <button class="btn btn-secondary" style="justify-content:flex-start;" onclick="duplicateInvoice({{ $invoice->id }})">
          <i class="fas fa-copy"></i> Dupliquer
        </button>
        @if(!in_array($invoice->status, ['paid']))
        <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-danger);border-color:var(--c-danger-lt);" onclick="deleteInvoice({{ $invoice->id }})">
          <i class="fas fa-trash"></i> Supprimer
        </button>
        @endif
      </div>
    </div>

  </div>
</div>

{{-- Payment Modal --}}
<div class="modal-overlay" id="paymentModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-success-lt);color:var(--c-success)">
        <i class="fas fa-credit-card"></i>
      </div>
      <div>
        <div class="modal-title">Enregistrer un paiement</div>
        <div class="modal-subtitle">Facture {{ $invoice->number }} · Reste dû : {{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="paymentForm" action="{{ route('invoices.payments.store', $invoice) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Montant <span class="required">*</span></label>
              <div class="input-group input-right">
                <input type="number" name="amount" class="form-control" value="{{ $invoice->amount_due }}" min="0.01" step="any" required>
                <i class="fas fa-euro-sign input-icon"></i>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Devise <span class="required">*</span></label>
              <select name="currency" class="form-control">
                @foreach(config('invoice.currencies') as $code => $cfg)
                  <option value="{{ $code }}" {{ $code === $invoice->currency ? 'selected' : '' }}>
                    {{ $code }} {{ $cfg['symbol'] }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Date de paiement <span class="required">*</span></label>
              <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Mode de paiement <span class="required">*</span></label>
              <select name="payment_method" class="form-control" required>
                @foreach(config('invoice.payment_methods') as $key => $label)
                  <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Référence</label>
              <input type="text" name="reference" class="form-control" placeholder="N° chèque, virement…">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Banque</label>
              <input type="text" name="bank_name" class="form-control" placeholder="Nom de la banque">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2" style="min-height:70px;"></textarea>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Justificatif <span class="hint">(PDF, image)</span></label>
              <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-success" id="paymentSubmitBtn" onclick="submitPayment()">
        <i class="fas fa-circle-check"></i> Enregistrer le paiement
      </button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
async function sendInvoice(id) {
  if (!confirm('Marquer cette facture comme envoyée ?')) return;
  const { ok, data } = await Http.post(`/invoices/${id}/send`, {});
  if (ok) { Toast.success('Envoyée', data.message); setTimeout(() => location.reload(), 1000); }
  else Toast.error('Erreur', data.message);
}

async function duplicateInvoice(id) {
  const { ok, data } = await Http.post(`/invoices/${id}/duplicate`, {});
  if (ok) { Toast.success('Dupliquée', data.message); setTimeout(() => window.location.href = data.redirect, 1000); }
  else Toast.error('Erreur', data.message);
}

async function deleteInvoice(id) {
  Modal.confirm({
    title: 'Supprimer cette facture ?',
    message: 'Cette action est irréversible.',
    confirmText: 'Supprimer',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(`/invoices/${id}`);
      if (ok) { Toast.success('Supprimée', data.message); setTimeout(() => window.location.href = '{{ route("invoices.index") }}', 1000); }
      else Toast.error('Erreur', data.message);
    }
  });
}

async function deletePayment(id) {
  Modal.confirm({
    title: 'Supprimer ce paiement ?',
    message: 'Cette action recalculera le montant dû.',
    confirmText: 'Supprimer',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(`/invoices/payments/${id}`);
      if (ok) { Toast.success('Supprimé', data.message); setTimeout(() => location.reload(), 1000); }
      else Toast.error('Erreur', data.message);
    }
  });
}

async function submitPayment() {
  const btn  = document.getElementById('paymentSubmitBtn');
  const form = document.getElementById('paymentForm');
  CrmForm.clearErrors(form);
  CrmForm.setLoading(btn, true);
  const { ok, data, status } = await Http.post(form.action, new FormData(form));
  CrmForm.setLoading(btn, false);
  if (ok) {
    Toast.success('Paiement enregistré !', data.message);
    Modal.close(document.getElementById('paymentModal'));
    setTimeout(() => location.reload(), 1000);
  } else if (status === 422) {
    CrmForm.showErrors(form, data.errors || {});
    Toast.error('Validation', data.message);
  } else {
    Toast.error('Erreur', data.message);
  }
}
</script>
@endpush