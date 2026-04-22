@extends('layouts.global')

@section('title', 'Tableau de bord')

@push('styles')
<style>
  .stats-grid {
    grid-template-columns: repeat(auto-fill, minmax(235px, 1fr));
    gap: 14px;
  }
  .stats-grid .stat-card {
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    border: 1px solid var(--c-ink-05);
    background:
      radial-gradient(circle at top right, rgba(37, 99, 235, .10), transparent 55%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
  }
  .stats-grid .stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--c-accent), #38bdf8);
    opacity: .9;
  }
  .stats-grid .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 30px rgba(15, 23, 42, .11);
    border-color: #bfdbfe;
  }
  .stats-grid .stat-card.stat-down::before {
    background: linear-gradient(90deg, #f97316, #ef4444);
  }
  .stats-grid .stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .45), 0 6px 16px rgba(15, 23, 42, .08);
  }
  .stats-grid .stat-body {
    min-width: 0;
  }
  .stats-grid .stat-value {
    font-size: 28px;
    line-height: 1;
    letter-spacing: -.02em;
    margin-bottom: 6px;
    color: #0b1220;
  }
  .stats-grid .stat-label {
    font-size: 11px;
    letter-spacing: .06em;
    color: #475569;
  }
  .stats-grid .stat-trend {
    margin-top: 7px;
    font-size: 11px;
    font-weight: 700;
  }
  .stats-grid .stat-trend i {
    font-size: 10px;
  }
  .dashboard-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 16px;
    margin-bottom: 18px;
  }
  .dashboard-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .dashboard-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid var(--c-ink-05);
    border-radius: 10px;
    background: var(--surface-0);
  }
  .dashboard-list-item strong {
    font-size: 13px;
    color: var(--c-ink);
  }
  .dashboard-list-item small {
    color: var(--c-ink-40);
    font-size: 12px;
  }
  .dashboard-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--surface-2);
    color: var(--c-ink-60);
    font-weight: 600;
  }
  .history-empty {
    border: 1px dashed var(--c-ink-10);
    border-radius: 12px;
    padding: 18px;
    text-align: center;
    color: var(--c-ink-40);
    font-size: 13px;
  }
  .quick-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  @media (max-width: 1100px) {
    .dashboard-grid {
      grid-template-columns: 1fr;
    }
  }
  @media (max-width: 720px) {
    .stats-grid {
      grid-template-columns: 1fr;
      gap: 10px;
    }
    .stats-grid .stat-card {
      padding: 16px;
    }
    .stats-grid .stat-value {
      font-size: 24px;
    }
  }
</style>
@endpush

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Tableau de bord</h1>
    <p>Vue d’ensemble de votre activité CRM, des performances et de l’historique récent.</p>
  </div>
  <div class="page-header-actions quick-actions">
    @if(Route::has('settings.global'))
      <a href="{{ route('settings.global') }}" class="btn btn-secondary"><i class="fas fa-sliders"></i> Paramètres globaux</a>
    @endif
    @if(Route::has('marketplace.my-apps'))
      <a href="{{ route('marketplace.my-apps') }}" class="btn btn-primary"><i class="fas fa-th-large"></i> Mes applications</a>
    @endif
  </div>
</div>

<div class="stats-grid">
  @foreach(($statsCards ?? []) as $card)
    <article class="stat-card stat-{{ ($card['trend_type'] ?? 'up') === 'up' ? 'up' : 'down' }}">
      <div class="stat-icon" style="{{ $card['icon_style'] ?? '' }}">
        <i class="fas {{ $card['icon'] ?? 'fa-chart-line' }}"></i>
      </div>
      <div class="stat-body">
        <div class="stat-value">{{ $card['value'] ?? '0' }}</div>
        <div class="stat-label">{{ $card['label'] ?? 'Stat' }}</div>
        <span class="stat-trend {{ ($card['trend_type'] ?? 'up') === 'up' ? 'up' : 'down' }}">
          <i class="fas {{ ($card['trend_type'] ?? 'up') === 'up' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
          {{ $card['trend'] ?? '' }}
        </span>
      </div>
    </article>
  @endforeach
</div>

<div class="dashboard-grid">
  <section class="info-card">
    <div class="info-card-header">
      <i class="fas fa-clock-rotate-left"></i>
      <h3>Historique d’activité</h3>
    </div>
    <div class="info-card-body">
      @if(($history ?? collect())->count())
        <ul class="timeline">
          @foreach(($history ?? collect()) as $event)
            <li class="timeline-item">
              <div class="timeline-icon"><i class="fas {{ $event['icon'] ?? 'fa-bolt' }}"></i></div>
              <div class="timeline-content">
                @if(!empty($event['url']))
                  <a href="{{ $event['url'] }}" class="timeline-title" style="text-decoration:none;color:var(--c-ink);">{{ $event['title'] ?? 'Événement' }}</a>
                @else
                  <p class="timeline-title">{{ $event['title'] ?? 'Événement' }}</p>
                @endif
                <div class="timeline-meta">
                  {{ $event['description'] ?? '' }}
                  @if(!empty($event['at']))
                    · {{ \Illuminate\Support\Carbon::parse($event['at'])->locale('fr')->diffForHumans() }}
                  @endif
                </div>
              </div>
            </li>
          @endforeach
        </ul>
      @else
        <div class="history-empty">Aucune activité récente à afficher pour le moment.</div>
      @endif
    </div>
  </section>

  <div class="dashboard-list">
    <section class="info-card">
      <div class="info-card-header">
        <i class="fas fa-layer-group"></i>
        <h3>Applications installées par catégorie</h3>
      </div>
      <div class="info-card-body">
        @if(($installedByCategory ?? collect())->count())
          <div class="dashboard-list">
            @foreach(($installedByCategory ?? collect()) as $category)
              <div class="dashboard-list-item">
                <div>
                  <strong>{{ $category['label'] ?? 'Autre' }}</strong><br>
                  <small>{{ implode(' · ', array_slice($category['apps'] ?? [], 0, 3)) }}{{ count($category['apps'] ?? []) > 3 ? ' ...' : '' }}</small>
                </div>
                <span class="dashboard-chip">
                  <i class="fas fa-cubes"></i>
                  {{ (int) ($category['count'] ?? 0) }}
                </span>
              </div>
            @endforeach
          </div>
        @else
          <div class="history-empty">Aucune application active sur ce tenant.</div>
        @endif
      </div>
    </section>

    <section class="info-card">
      <div class="info-card-header">
        <i class="fas fa-gauge-high"></i>
        <h3>Synthèse modules</h3>
      </div>
      <div class="info-card-body">
        <div class="dashboard-list">
          @foreach(($moduleSummary ?? []) as $module)
            <div class="dashboard-list-item">
              <div>
                @if(!empty($module['route']))
                  <a href="{{ $module['route'] }}" style="text-decoration:none;color:var(--c-ink);font-weight:600;">
                    <i class="fas {{ $module['icon'] ?? 'fa-square' }}" style="color:var(--c-accent);margin-right:6px;"></i>
                    {{ $module['name'] ?? 'Module' }}
                  </a>
                @else
                  <strong>
                    <i class="fas {{ $module['icon'] ?? 'fa-square' }}" style="color:var(--c-accent);margin-right:6px;"></i>
                    {{ $module['name'] ?? 'Module' }}
                  </strong>
                @endif
              </div>
              <span class="dashboard-chip">{{ number_format((int) ($module['count'] ?? 0), 0, ',', ' ') }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  </div>
</div>

<div class="row">
  <div class="col-8">
    <section class="table-wrapper">
      <div class="table-header">
        <div class="table-title">Factures récentes</div>
        <span class="table-count">{{ ($recentInvoices ?? collect())->count() }}</span>
        <div class="table-spacer"></div>
        @if(Route::has('invoices.index'))
          <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-right"></i> Voir tout</a>
        @endif
      </div>

      @if(($recentInvoices ?? collect())->count())
        <table class="crm-table">
          <thead>
            <tr>
              <th>Numéro</th>
              <th>Client</th>
              <th>Statut</th>
              <th>Total</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            @foreach(($recentInvoices ?? collect()) as $invoice)
              <tr>
                <td>
                  @if(Route::has('invoices.show'))
                    <a href="{{ route('invoices.show', $invoice->id) }}" style="text-decoration:none;color:var(--c-accent);font-weight:600;">
                      {{ $invoice->number ?: ('#' . $invoice->id) }}
                    </a>
                  @else
                    {{ $invoice->number ?: ('#' . $invoice->id) }}
                  @endif
                </td>
                <td>{{ $invoice->client?->company_name ?: '—' }}</td>
                <td><span class="badge">{{ ucfirst((string) ($invoice->status ?? 'draft')) }}</span></td>
                <td>{{ number_format((float) ($invoice->total ?? 0), 2, ',', ' ') }} {{ strtoupper((string) ($invoice->currency ?: ($tenant->currency ?? 'EUR'))) }}</td>
                <td>{{ optional($invoice->issue_date)->format('d/m/Y') ?: '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <div class="table-empty">
          <div class="table-empty-icon"><i class="fas fa-file-invoice"></i></div>
          <h3>Aucune facture récente</h3>
          <p>Les dernières factures créées apparaîtront ici.</p>
        </div>
      @endif
    </section>
  </div>

  <div class="col-4">
    <section class="table-wrapper">
      <div class="table-header">
        <div class="table-title">Tâches à échéance (7 jours)</div>
        <span class="table-count">{{ ($upcomingTasks ?? collect())->count() }}</span>
      </div>
      @if(($upcomingTasks ?? collect())->count())
        <div class="info-card-body">
          <div class="dashboard-list">
            @foreach(($upcomingTasks ?? collect()) as $task)
              <div class="dashboard-list-item">
                <div>
                  <strong>{{ \Illuminate\Support\Str::limit((string) $task->title, 38) }}</strong><br>
                  <small>{{ $task->project?->name ?: 'Projet non défini' }} · {{ optional($task->due_date)->format('d/m/Y') ?: 'Sans date' }}</small>
                </div>
                <span class="dashboard-chip">{{ strtoupper((string) ($task->status ?? 'todo')) }}</span>
              </div>
            @endforeach
          </div>
        </div>
      @else
        <div class="table-empty" style="padding:24px 14px;">
          <div class="table-empty-icon" style="width:52px;height:52px;"><i class="fas fa-list-check"></i></div>
          <h3>Rien d’urgent</h3>
          <p>Aucune tâche à échéance dans les 7 prochains jours.</p>
        </div>
      @endif
    </section>
  </div>
</div>
@endsection
