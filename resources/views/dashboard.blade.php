@extends('layouts.global')

@section('title', __('dashboard.page_title'))

@push('styles')
<style>
  .dashboard-shell {
    --db-ink: #081225;
    --db-ink-soft: #314155;
    --db-muted: #62748a;
    --db-accent: #2563eb;
    --db-accent-deep: #1d4ed8;
    --db-shadow: 0 20px 48px rgba(15, 23, 42, .08);
    display: flex;
    flex-direction: column;
    gap: 22px;
    color: var(--db-ink);
  }
  .dashboard-page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    padding-bottom: 4px;
  }
  .dashboard-page-header h1 {
    margin: 0;
    font-size: clamp(30px, 4vw, 44px);
    line-height: .96;
    letter-spacing: -.05em;
    color: var(--db-ink);
  }
  .dashboard-page-header p {
    margin: 10px 0 0;
    max-width: 760px;
    color: var(--db-muted);
    font-size: 15px;
    line-height: 1.65;
  }
  .dashboard-page-header .quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
  .dashboard-page-header .btn {
    min-height: 44px;
    border-radius: 14px;
    padding-inline: 16px;
    box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
  }
  .dashboard-command-panel {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(280px, .95fr);
    gap: 20px;
    padding: 28px;
    border-radius: 30px;
    background:
      radial-gradient(circle at 14% 18%, rgba(59, 130, 246, .16), transparent 28%),
      radial-gradient(circle at 88% 20%, rgba(14, 165, 233, .16), transparent 24%),
      linear-gradient(145deg, #fdfefe 0%, #f7fbff 46%, #eef5ff 100%);
    border: 1px solid rgba(148, 163, 184, .18);
    box-shadow: 0 28px 60px rgba(15, 23, 42, .10);
    isolation: isolate;
  }
  .dashboard-command-panel::before,
  .dashboard-command-panel::after {
    content: '';
    position: absolute;
    pointer-events: none;
    z-index: -1;
  }
  .dashboard-command-panel::before {
    width: 240px;
    height: 240px;
    right: -70px;
    top: -90px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(37, 99, 235, .16) 0%, rgba(37, 99, 235, 0) 72%);
  }
  .dashboard-command-panel::after {
    width: 320px;
    height: 320px;
    left: -110px;
    bottom: -170px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(14, 165, 233, .12) 0%, rgba(14, 165, 233, 0) 72%);
  }
  .dashboard-command-copy {
    display: flex;
    flex-direction: column;
    gap: 16px;
    justify-content: center;
  }
  .dashboard-command-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, .86);
    border: 1px solid rgba(37, 99, 235, .14);
    color: var(--db-accent-deep);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
    box-shadow: 0 12px 20px rgba(15, 23, 42, .05);
  }
  .dashboard-command-copy h2 {
    margin: 0;
    font-size: clamp(28px, 3vw, 40px);
    line-height: 1;
    letter-spacing: -.05em;
    color: var(--db-ink);
  }
  .dashboard-command-copy p {
    margin: 0;
    max-width: 720px;
    font-size: 15px;
    line-height: 1.75;
    color: var(--db-ink-soft);
  }
  .dashboard-command-highlights {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }
  .command-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, .9);
    border: 1px solid rgba(148, 163, 184, .16);
    box-shadow: 0 10px 20px rgba(15, 23, 42, .05);
    font-size: 12px;
    font-weight: 700;
    color: var(--db-ink-soft);
  }
  .command-pill i {
    color: var(--db-accent);
  }
  .dashboard-command-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    align-content: center;
  }
  .command-metric {
    position: relative;
    overflow: hidden;
    padding: 16px;
    min-height: 116px;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, .18);
    background: rgba(255, 255, 255, .82);
    backdrop-filter: blur(10px);
    box-shadow: 0 16px 28px rgba(15, 23, 42, .06);
  }
  .command-metric::before {
    content: '';
    position: absolute;
    inset: 0 0 auto;
    height: 3px;
    background: linear-gradient(90deg, rgba(37, 99, 235, .95), rgba(14, 165, 233, .85));
  }
  .command-metric span {
    display: block;
    font-size: 12px;
    color: var(--db-muted);
    margin-bottom: 10px;
  }
  .command-metric strong {
    display: block;
    font-size: 34px;
    line-height: 1;
    letter-spacing: -.05em;
    color: var(--db-ink);
    margin-bottom: 8px;
  }
  .command-metric small {
    display: block;
    font-size: 12px;
    color: var(--db-ink-soft);
    line-height: 1.5;
  }
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
  }
  .stats-grid .stat-card {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-rows: auto 1fr auto;
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, .14);
    background:
      radial-gradient(circle at top right, rgba(37, 99, 235, .12), transparent 42%),
      linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
    box-shadow: 0 22px 44px rgba(15, 23, 42, .08);
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    padding: 20px;
    min-height: 220px;
  }
  .stats-grid .stat-card::before {
    content: '';
    position: absolute;
    inset: 0 auto auto 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--db-accent), #38bdf8);
    opacity: .95;
  }
  .stats-grid .stat-card::after {
    content: '';
    position: absolute;
    inset: auto -30px -40px auto;
    width: 132px;
    height: 132px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(37, 99, 235, .09) 0%, rgba(37, 99, 235, 0) 72%);
    pointer-events: none;
  }
  .stats-grid .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 28px 54px rgba(15, 23, 42, .12);
    border-color: rgba(37, 99, 235, .22);
  }
  .stats-grid .stat-card.stat-down::before {
    background: linear-gradient(90deg, #f97316, #ef4444);
  }
  .stats-grid .stat-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }
  .stats-grid .stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .46), 0 12px 22px rgba(15, 23, 42, .08);
  }
  .stats-grid .stat-value {
    font-size: 34px;
    line-height: .96;
    letter-spacing: -.04em;
    font-weight: 800;
    color: var(--db-ink);
    margin: 0 0 10px;
    align-self: center;
    justify-self: end;
    text-align: right;
    max-width: 88%;
  }
  .stats-grid .stat-label {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -.04em;
    text-transform: none;
    color: var(--db-ink);
    max-width: 78%;
    line-height: 1.02;
  }
  .stats-grid .stat-trend {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 800;
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(37, 99, 235, .08);
    color: #1d4ed8;
    width: 100%;
    justify-content: flex-start;
    align-self: end;
  }
  .stats-grid .stat-card.stat-down .stat-trend {
    background: rgba(249, 115, 22, .12);
    color: #c2410c;
  }
  .dashboard-hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(360px, .98fr);
    gap: 18px;
    align-items: stretch;
  }
  .dashboard-panel {
    position: relative;
    border: 1px solid rgba(148, 163, 184, .14);
    border-radius: 28px;
    background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    box-shadow: var(--db-shadow);
    overflow: hidden;
  }
  .dashboard-panel::before {
    content: '';
    position: absolute;
    inset: 0 0 auto;
    height: 1px;
    background: linear-gradient(90deg, rgba(255, 255, 255, .96), rgba(255, 255, 255, .22));
  }
  .dashboard-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 22px 24px 0;
  }
  .dashboard-panel-title {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .dashboard-panel-title i {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(37, 99, 235, .10);
    color: var(--db-accent);
    font-size: 17px;
    box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .10);
  }
  .dashboard-panel-title h3 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
    color: var(--db-ink);
    letter-spacing: -.02em;
  }
  .dashboard-panel-title p {
    margin: 4px 0 0;
    font-size: 13px;
    color: var(--db-muted);
    line-height: 1.55;
  }
  .chart-panel-body {
    padding: 14px 24px 24px;
  }
  .chart-canvas-wrap {
    position: relative;
    min-height: 300px;
  }
  .chart-canvas-wrap.compact {
    min-height: 246px;
  }
  .dashboard-mini-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 18px;
  }
  .dashboard-meta-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 12px;
    margin-top: 18px;
  }
  .dashboard-meta-box {
    border: 1px solid rgba(148, 163, 184, .14);
    border-radius: 18px;
    padding: 15px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(245, 249, 255, .94));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .6);
  }
  .dashboard-meta-box strong {
    display: block;
    font-size: 24px;
    line-height: 1;
    color: var(--db-ink);
    margin-bottom: 6px;
  }
  .dashboard-meta-box span {
    font-size: 12px;
    color: var(--db-muted);
    line-height: 1.5;
  }
  .integration-health {
    padding: 16px 24px 0;
    display: grid;
    grid-template-columns: 190px minmax(0, 1fr);
    gap: 16px;
    align-items: center;
  }
  .integration-health-summary {
    display: grid;
    gap: 10px;
  }
  .integration-health-summary .metric {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 14px;
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(245, 249, 255, .92));
    border: 1px solid rgba(148, 163, 184, .14);
    color: var(--db-ink-soft);
    font-size: 13px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, .04);
  }
  .integration-health-summary .metric.connected {
    background: linear-gradient(180deg, rgba(236, 253, 245, .98), rgba(220, 252, 231, .92));
    border-color: rgba(22, 163, 74, .20);
    color: #166534;
  }
  .integration-health-summary .metric.connected strong {
    color: #166534;
  }
  .integration-health-summary .metric.attention {
    background: linear-gradient(180deg, rgba(255, 247, 237, .98), rgba(255, 237, 213, .92));
    border-color: rgba(234, 88, 12, .20);
    color: #9a3412;
  }
  .integration-health-summary .metric.attention strong {
    color: #c2410c;
  }
  .integration-health-summary .metric.installed {
    background: linear-gradient(180deg, rgba(239, 246, 255, .98), rgba(219, 234, 254, .92));
    border-color: rgba(37, 99, 235, .18);
    color: #1d4ed8;
  }
  .integration-health-summary .metric.installed strong {
    color: #1d4ed8;
  }
  .integration-health-summary .metric strong {
    font-size: 19px;
    color: var(--db-ink);
  }
  .integration-card-list {
    display: grid;
    gap: 12px;
    padding: 18px 24px 24px;
    max-height: 332px;
    overflow-y: auto;
    padding-right: 12px;
    scrollbar-width: thin;
    scrollbar-color: rgba(59, 130, 246, .45) rgba(226, 232, 240, .7);
  }
  .integration-card-list::-webkit-scrollbar {
    width: 8px;
  }
  .integration-card-list::-webkit-scrollbar-track {
    background: rgba(226, 232, 240, .72);
    border-radius: 999px;
  }
  .integration-card-list::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, .42);
    border-radius: 999px;
  }
  .integration-card-list::-webkit-scrollbar-thumb:hover {
    background: rgba(37, 99, 235, .60);
  }
  .integration-card {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    padding: 15px;
    border-radius: 22px;
    border: 1px solid rgba(148, 163, 184, .14);
    background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    text-decoration: none;
    box-shadow: 0 16px 28px rgba(15, 23, 42, .04);
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
  }
  .integration-card:hover {
    transform: translateY(-3px);
    border-color: rgba(37, 99, 235, .20);
    box-shadow: 0 22px 36px rgba(15, 23, 42, .08);
  }
  .integration-card-icon {
    width: 52px;
    height: 52px;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 12px 24px rgba(15, 23, 42, .12);
    overflow: hidden;
    flex-shrink: 0;
  }
  .integration-card-icon img {
    width: 26px;
    height: 26px;
    object-fit: contain;
    display: block;
  }
  .integration-card-copy {
    min-width: 0;
  }
  .integration-card-copy strong {
    display: block;
    font-size: 15px;
    color: var(--db-ink);
  }
  .integration-card-copy small {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: var(--db-muted);
    line-height: 1.55;
  }
  .integration-card-copy .meta {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
  }
  .integration-card-copy .meta span {
    font-size: 11px;
    padding: 5px 9px;
    border-radius: 999px;
    background: rgba(37, 99, 235, .08);
    color: var(--db-accent);
    font-weight: 800;
  }
  .integration-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 11px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .integration-status.connected {
    background: rgba(22, 163, 74, .12);
    color: #15803d;
  }
  .integration-status.attention {
    background: rgba(234, 88, 12, .12);
    color: #c2410c;
  }
  .integration-status.installed {
    background: rgba(37, 99, 235, .10);
    color: var(--db-accent);
  }
  .dashboard-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(340px, 1fr);
    gap: 18px;
  }
  .dashboard-stack {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }
  .dashboard-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .dashboard-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 15px;
    border: 1px solid rgba(148, 163, 184, .14);
    border-radius: 18px;
    background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
  }
  .dashboard-list-item:hover {
    transform: translateY(-2px);
    border-color: rgba(37, 99, 235, .18);
    box-shadow: 0 14px 26px rgba(15, 23, 42, .05);
  }
  .dashboard-list-item strong,
  .dashboard-list-item a {
    font-size: 14px;
    color: var(--db-ink);
    font-weight: 700;
    text-decoration: none;
  }
  .dashboard-list-item small {
    color: var(--db-muted);
    font-size: 12px;
    line-height: 1.45;
  }
  .dashboard-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    padding: 7px 11px;
    border-radius: 999px;
    background: rgba(37, 99, 235, .08);
    color: var(--db-accent);
    font-weight: 800;
    white-space: nowrap;
  }
  .dashboard-data-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 18px;
  }
  .table-wrapper.dashboard-table {
    border-radius: 28px;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, .14);
    background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    box-shadow: var(--db-shadow);
  }
  .table-wrapper.dashboard-table .table-header {
    padding: 22px 22px 0;
  }
  .table-wrapper.dashboard-table .table-title {
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -.02em;
    color: var(--db-ink);
  }
  .table-wrapper.dashboard-table .table-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(37, 99, 235, .08);
    color: var(--db-accent);
    font-weight: 800;
  }
  .table-wrapper.dashboard-table .crm-table {
    margin-top: 16px;
  }
  .table-wrapper.dashboard-table .crm-table thead th {
    background: rgba(248, 250, 252, .92);
    color: var(--db-muted);
    font-size: 11px;
    letter-spacing: .12em;
    text-transform: uppercase;
    font-weight: 800;
    border-bottom: 1px solid rgba(148, 163, 184, .16);
  }
  .table-wrapper.dashboard-table .crm-table tbody td {
    color: var(--db-ink-soft);
    border-bottom: 1px solid rgba(148, 163, 184, .12);
  }
  .table-wrapper.dashboard-table .crm-table tbody tr:hover {
    background: rgba(239, 246, 255, .78);
  }
  .table-wrapper.dashboard-table .crm-table tbody tr:last-child td {
    border-bottom: none;
  }
  .timeline {
    margin: 0;
    padding: 6px 0 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  .timeline-item {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 14px;
    align-items: flex-start;
  }
  .timeline-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(37, 99, 235, .10);
    color: var(--db-accent);
    box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .08);
  }
  .timeline-content {
    padding: 14px 16px;
    border-radius: 18px;
    border: 1px solid rgba(148, 163, 184, .14);
    background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
  }
  .timeline-title {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    color: var(--db-ink);
  }
  .timeline-meta {
    margin-top: 6px;
    color: var(--db-muted);
    font-size: 12px;
    line-height: 1.6;
  }
  .history-empty,
  .module-empty {
    border: 1px dashed rgba(148, 163, 184, .22);
    border-radius: 20px;
    padding: 24px 20px;
    text-align: center;
    color: var(--db-muted);
    font-size: 13px;
    background: rgba(248, 250, 252, .72);
  }
  .module-empty strong,
  .history-empty strong {
    display: block;
    margin-bottom: 6px;
    color: var(--db-ink);
  }
  .table-empty {
    padding: 30px 24px 34px;
  }
  .table-empty .table-empty-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    margin: 0 auto 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(37, 99, 235, .10);
    color: var(--db-accent);
  }
  .table-empty h3 {
    margin: 0 0 6px;
    font-size: 18px;
    color: var(--db-ink);
  }
  .table-empty p {
    margin: 0;
    color: var(--db-muted);
  }
  @media (max-width: 1260px) {
    .dashboard-command-panel,
    .dashboard-hero-grid,
    .dashboard-grid {
      grid-template-columns: 1fr;
    }
    .integration-health {
      grid-template-columns: 1fr;
    }
  }
  @media (max-width: 860px) {
    .dashboard-page-header {
      flex-direction: column;
      align-items: flex-start;
    }
    .dashboard-command-panel {
      padding: 22px;
    }
    .dashboard-command-metrics {
      grid-template-columns: 1fr 1fr;
    }
  }
  @media (max-width: 720px) {
    .stats-grid,
    .dashboard-mini-grid,
    .dashboard-data-grid,
    .dashboard-command-metrics {
      grid-template-columns: 1fr;
    }
    .dashboard-panel-header,
    .chart-panel-body,
    .integration-card-list,
    .integration-health {
      padding-left: 18px;
      padding-right: 18px;
    }
    .integration-card {
      grid-template-columns: auto minmax(0, 1fr);
    }
    .integration-card > .integration-status {
      grid-column: 1 / -1;
      justify-self: flex-start;
    }
    .dashboard-command-copy h2 {
      font-size: 28px;
    }
    .stats-grid .stat-value {
      font-size: 30px;
    }
  }
</style>
@endpush

@section('content')
@php
  $moduleCount = collect($moduleSummary ?? [])->count();
  $connectedIntegrationCount = (int) data_get($integrationChart ?? [], 'data.0', 0);
  $attentionIntegrationCount = (int) data_get($integrationChart ?? [], 'data.1', 0);
  $installedIntegrationCount = collect($integrationCards ?? [])->count();
  $historyCount = collect($history ?? [])->count();
  $tenantDisplayName = $tenant->name ?? ('Tenant #' . ($currentTenantId ?? 0));
@endphp

<div class="page-header dashboard-page-header">
  <div class="page-header-left">
    <h1>{{ __('dashboard.header.title') }}</h1>
    <p>{{ __('dashboard.header.description') }}</p>
  </div>
  <div class="page-header-actions quick-actions">
    @if(Route::has('settings.global'))
      <a href="{{ route('settings.global') }}" class="btn btn-secondary"><i class="fas fa-sliders"></i> {{ __('dashboard.header.global_settings') }}</a>
    @endif
    @if(Route::has('marketplace.my-apps'))
      <a href="{{ route('marketplace.my-apps') }}" class="btn btn-primary"><i class="fas fa-th-large"></i> {{ __('dashboard.header.my_apps') }}</a>
    @endif
  </div>
</div>

<div class="dashboard-shell">
  <section class="dashboard-command-panel">
    <div class="dashboard-command-copy">
      <span class="dashboard-command-eyebrow">
        <i class="fas fa-wave-square"></i>
        {{ __('dashboard.command.eyebrow') }}
      </span>
      <h2>{{ $tenantDisplayName }}</h2>
      <p>
        {{ __('dashboard.command.description') }}
      </p>
      <div class="dashboard-command-highlights">
        <span class="command-pill"><i class="fas fa-layer-group"></i> {{ __('dashboard.command.highlights.modules', ['count' => $moduleCount]) }}</span>
        <span class="command-pill"><i class="fas fa-plug-circle-check"></i> {{ __('dashboard.command.highlights.connected', ['count' => $connectedIntegrationCount]) }}</span>
        <span class="command-pill"><i class="fas fa-triangle-exclamation"></i> {{ __('dashboard.command.highlights.attention', ['count' => $attentionIntegrationCount]) }}</span>
        <span class="command-pill"><i class="fas fa-clock-rotate-left"></i> {{ __('dashboard.command.highlights.history', ['count' => $historyCount]) }}</span>
      </div>
    </div>
    <div class="dashboard-command-metrics">
      <div class="command-metric">
        <span>{{ __('dashboard.command.metrics.installed_apps_title') }}</span>
        <strong>{{ $installedIntegrationCount }}</strong>
        <small>{{ __('dashboard.command.metrics.installed_apps_description') }}</small>
      </div>
      <div class="command-metric">
        <span>{{ __('dashboard.command.metrics.active_modules_title') }}</span>
        <strong>{{ $moduleCount }}</strong>
        <small>{{ __('dashboard.command.metrics.active_modules_description') }}</small>
      </div>
      <div class="command-metric">
        <span>{{ __('dashboard.command.metrics.ready_integrations_title') }}</span>
        <strong>{{ $connectedIntegrationCount }}</strong>
        <small>{{ __('dashboard.command.metrics.ready_integrations_description') }}</small>
      </div>
      <div class="command-metric">
        <span>{{ __('dashboard.command.metrics.tenant_currency_title') }}</span>
        <strong>{{ strtoupper((string) ($tenant->currency ?? 'EUR')) }}</strong>
        <small>{{ __('dashboard.command.metrics.tenant_currency_description') }}</small>
      </div>
    </div>
  </section>

  @if(!empty($statsCards))
    <div class="stats-grid">
      @foreach($statsCards as $card)
        <article class="stat-card stat-{{ ($card['trend_type'] ?? 'up') === 'up' ? 'up' : 'down' }}">
          <div class="stat-head">
            <div class="stat-label">{{ $card['label'] ?? __('dashboard.stats.fallback_label') }}</div>
            <div class="stat-icon" style="{{ $card['icon_style'] ?? '' }}">
              <i class="fas {{ $card['icon'] ?? 'fa-chart-line' }}"></i>
            </div>
          </div>
          <div class="stat-value">{{ $card['value'] ?? '0' }}</div>
          <span class="stat-trend">
            <i class="fas {{ ($card['trend_type'] ?? 'up') === 'up' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
            {{ $card['trend'] ?? '' }}
          </span>
        </article>
      @endforeach
    </div>
  @endif

  <div class="dashboard-hero-grid">
    <section class="dashboard-panel">
      <div class="dashboard-panel-header">
        <div class="dashboard-panel-title">
          <i class="fas fa-chart-column"></i>
          <div>
            <h3>{{ __('dashboard.modules.title') }}</h3>
            <p>{{ __('dashboard.modules.description') }}</p>
          </div>
        </div>
      </div>
      <div class="chart-panel-body">
        @if(!empty($moduleChart['labels']))
          <div class="chart-canvas-wrap">
            <canvas id="dashboardModuleChart"></canvas>
          </div>
          <div class="dashboard-meta-strip">
            @foreach($moduleSummary as $module)
              <div class="dashboard-meta-box">
                <strong>{{ number_format((int) ($module['count'] ?? 0), 0, ',', ' ') }}</strong>
                <span>{{ $module['name'] ?? __('dashboard.modules.fallback_name') }} &middot; {{ $module['description'] ?? __('dashboard.modules.fallback_status') }}</span>
              </div>
            @endforeach
          </div>
        @else
          <div class="module-empty">
            <strong>{{ __('dashboard.modules.empty_title') }}</strong>
            <p style="margin:8px 0 0;">{{ __('dashboard.modules.empty_description') }}</p>
          </div>
        @endif
      </div>
    </section>

    <section class="dashboard-panel">
      @if(!empty($integrationCards))
        <div class="integration-health">
          <div class="chart-canvas-wrap compact">
            <canvas id="dashboardIntegrationChart"></canvas>
          </div>
          <div class="integration-health-summary">
            <div class="metric connected">
              <span>{{ __('dashboard.integrations.connected') }}</span>
              <strong>{{ $integrationChart['data'][0] ?? 0 }}</strong>
            </div>
            <div class="metric attention">
              <span>{{ __('dashboard.integrations.reconnect') }}</span>
              <strong>{{ $integrationChart['data'][1] ?? 0 }}</strong>
            </div>
            <div class="metric installed">
              <span>{{ __('dashboard.integrations.installed_without_connection') }}</span>
              <strong>{{ $integrationChart['data'][2] ?? 0 }}</strong>
            </div>
          </div>
        </div>
        <div class="integration-card-list">
          @foreach($integrationCards as $integration)
            @php
              $iconValue = (string) ($integration['icon'] ?? '');
              $isImageIcon = str_contains($iconValue, '/') || str_contains($iconValue, '.png') || str_contains($iconValue, '.svg') || str_contains($iconValue, '.jpg') || str_starts_with($iconValue, 'http');
              $iconUrl = \Illuminate\Support\Str::startsWith($iconValue, ['http://', 'https://'])
                  ? $iconValue
                  : (\Illuminate\Support\Str::startsWith($iconValue, ['/'])
                      ? $iconValue
                      : (\Illuminate\Support\Str::startsWith($iconValue, ['storage/'])
                          ? asset($iconValue)
                          : asset('storage/' . ltrim($iconValue, '/'))));
              $iconClass = str_contains($iconValue, 'fa-') ? $iconValue : 'fas fa-plug';
            @endphp
            <a href="{{ $integration['url'] ?? '#' }}" class="integration-card">
              <span class="integration-card-icon" style="background: {{ $integration['color'] ?? '#2563eb' }};">
                @if($isImageIcon)
                  <img src="{{ $iconUrl }}" alt="{{ $integration['name'] ?? __('dashboard.integrations.fallback_name') }}">
                @else
                  <i class="{{ $iconClass }}"></i>
                @endif
              </span>
              <span class="integration-card-copy">
                <strong>{{ $integration['name'] ?? __('dashboard.integrations.fallback_name') }}</strong>
                <small>
                  @if(!empty($integration['account']))
                    {{ $integration['account'] }}
                  @else
                    {{ __('dashboard.integrations.installed_on_tenant') }}
                  @endif
                  @if(!empty($integration['context']))
                    &middot; {{ $integration['context'] }}
                  @endif
                  @if(!empty($integration['last_sync']))
                    &middot; sync {{ \Illuminate\Support\Carbon::parse($integration['last_sync'])->locale('fr')->diffForHumans() }}
                  @endif
                </small>
                <span class="meta">
                  <span>{{ number_format((int) ($integration['resource_count'] ?? 0), 0, ',', ' ') }} {{ $integration['resource_label'] ?? __('dashboard.integrations.resource_label') }}</span>
                </span>
              </span>
              <span class="integration-status {{ $integration['status'] ?? 'installed' }}">{{ $integration['status_label'] ?? __('dashboard.integrations.status_label') }}</span>
            </a>
          @endforeach
        </div>
      @else
        <div class="module-empty">
          <strong>{{ __('dashboard.integrations.empty_title') }}</strong>
          <p style="margin:8px 0 0;">{{ __('dashboard.integrations.empty_description') }}</p>
        </div>
      @endif
    </section>
  </div>

  @if($financeChart || $taskChart || $stockChart)
    <div class="dashboard-mini-grid">
      @if($financeChart)
        <section class="dashboard-panel">
          <div class="dashboard-panel-header">
            <div class="dashboard-panel-title">
              <i class="fas fa-sack-dollar"></i>
              <div>
                <h3>{{ __('dashboard.finance.title') }}</h3>
                <p>{{ __('dashboard.finance.description') }}</p>
              </div>
            </div>
          </div>
          <div class="chart-panel-body">
            <div class="chart-canvas-wrap compact">
              <canvas id="dashboardFinanceChart"></canvas>
            </div>
          </div>
        </section>
      @endif

      @if($taskChart)
        <section class="dashboard-panel">
          <div class="dashboard-panel-header">
            <div class="dashboard-panel-title">
              <i class="fas fa-list-check"></i>
              <div>
                <h3>{{ __('dashboard.tasks.title') }}</h3>
                <p>{{ __('dashboard.tasks.description') }}</p>
              </div>
            </div>
          </div>
          <div class="chart-panel-body">
            <div class="chart-canvas-wrap compact">
              <canvas id="dashboardTaskChart"></canvas>
            </div>
          </div>
        </section>
      @endif

      @if($stockChart)
        <section class="dashboard-panel">
          <div class="dashboard-panel-header">
            <div class="dashboard-panel-title">
              <i class="fas fa-boxes-stacked"></i>
              <div>
                <h3>{{ __('dashboard.stock.title') }}</h3>
                <p>{{ __('dashboard.stock.description') }}</p>
              </div>
            </div>
          </div>
          <div class="chart-panel-body">
            <div class="chart-canvas-wrap compact">
              <canvas id="dashboardStockChart"></canvas>
            </div>
          </div>
        </section>
      @endif
    </div>
  @endif

  <div class="dashboard-grid">
    <section class="dashboard-panel">
      <div class="dashboard-panel-header">
        <div class="dashboard-panel-title">
          <i class="fas fa-clock-rotate-left"></i>
          <div>
            <h3>{{ __('dashboard.history.title') }}</h3>
            <p>{{ __('dashboard.history.description') }}</p>
          </div>
        </div>
      </div>
      <div class="chart-panel-body">
        @if(collect($history ?? [])->count())
          <ul class="timeline">
            @foreach($history as $event)
              <li class="timeline-item">
                <div class="timeline-icon"><i class="fas {{ $event['icon'] ?? 'fa-bolt' }}"></i></div>
                <div class="timeline-content">
                  @if(!empty($event['url']))
                    <a href="{{ $event['url'] }}" class="timeline-title" style="text-decoration:none;color:var(--db-ink);">{{ $event['title'] ?? __('dashboard.history.fallback_event') }}</a>
                  @else
                    <p class="timeline-title">{{ $event['title'] ?? __('dashboard.history.fallback_event') }}</p>
                  @endif
                  <div class="timeline-meta">
                    {{ $event['description'] ?? '' }}
                    @if(!empty($event['at']))
                      &middot; {{ \Illuminate\Support\Carbon::parse($event['at'])->locale('fr')->diffForHumans() }}
                    @endif
                  </div>
                </div>
              </li>
            @endforeach
          </ul>
        @else
          <div class="history-empty">{{ __('dashboard.history.empty') }}</div>
        @endif
      </div>
    </section>

    <div class="dashboard-stack">
      <section class="dashboard-panel">
        <div class="dashboard-panel-header">
          <div class="dashboard-panel-title">
            <i class="fas fa-layer-group"></i>
            <div>
              <h3>{{ __('dashboard.categories.title') }}</h3>
              <p>{{ __('dashboard.categories.description') }}</p>
            </div>
          </div>
        </div>
        <div class="chart-panel-body">
          @if(collect($installedByCategory ?? [])->count())
            <div class="dashboard-list">
              @foreach($installedByCategory as $category)
                <div class="dashboard-list-item">
                  <div>
                    <strong>{{ $category['label'] ?? __('dashboard.categories.fallback') }}</strong><br>
                    <small>{{ implode(' · ', array_slice($category['apps'] ?? [], 0, 3)) }}{{ count($category['apps'] ?? []) > 3 ? ' …' : '' }}</small>
                  </div>
                  <span class="dashboard-chip">
                    <i class="fas fa-cubes"></i>
                    {{ (int) ($category['count'] ?? 0) }}
                  </span>
                </div>
              @endforeach
            </div>
          @else
            <div class="history-empty">{{ __('dashboard.categories.empty') }}</div>
          @endif
        </div>
      </section>

      <section class="dashboard-panel">
        <div class="dashboard-panel-header">
          <div class="dashboard-panel-title">
            <i class="fas fa-gauge-high"></i>
            <div>
              <h3>{{ __('dashboard.summary.title') }}</h3>
              <p>{{ __('dashboard.summary.description') }}</p>
            </div>
          </div>
        </div>
        <div class="chart-panel-body">
          @if(collect($moduleSummary ?? [])->count())
            <div class="dashboard-list">
              @foreach($moduleSummary as $module)
                <div class="dashboard-list-item">
                  <div>
                    @if(!empty($module['route']))
                      <a href="{{ $module['route'] }}">
                        <i class="fas {{ $module['icon'] ?? 'fa-square' }}" style="color:#2563eb;margin-right:7px;"></i>
                        {{ $module['name'] ?? __('dashboard.summary.fallback_name') }}
                      </a>
                    @else
                      <strong>
                        <i class="fas {{ $module['icon'] ?? 'fa-square' }}" style="color:#2563eb;margin-right:7px;"></i>
                        {{ $module['name'] ?? __('dashboard.summary.fallback_name') }}
                      </strong>
                    @endif
                    <br>
                    <small>{{ $module['description'] ?? __('dashboard.summary.fallback_status') }}</small>
                  </div>
                  <span class="dashboard-chip">{{ number_format((int) ($module['count'] ?? 0), 0, ',', ' ') }}</span>
                </div>
              @endforeach
            </div>
          @else
            <div class="history-empty">{{ __('dashboard.summary.empty') }}</div>
          @endif
        </div>
      </section>
    </div>
  </div>

  <div class="dashboard-data-grid">
    @if($hasClients)
      <section class="table-wrapper dashboard-table">
        <div class="table-header">
          <div class="table-title">{{ __('dashboard.tables.clients.title') }}</div>
          <span class="table-count">{{ collect($recentClients ?? [])->count() }}</span>
          <div class="table-spacer"></div>
          @if(Route::has('clients.index'))
            <a href="{{ route('clients.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-right"></i> {{ __('dashboard.tables.view_all') }}</a>
          @endif
        </div>
        @if(collect($recentClients ?? [])->count())
          <table class="crm-table">
            <thead>
              <tr>
                <th>{{ __('dashboard.tables.clients.company') }}</th>
                <th>{{ __('dashboard.tables.clients.contact') }}</th>
                <th>{{ __('dashboard.tables.clients.follow_up') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentClients as $client)
                <tr>
                  <td>
                    @if(Route::has('clients.show'))
                      <a href="{{ route('clients.show', $client->id) }}" style="text-decoration:none;color:var(--db-accent);font-weight:700;">
                        {{ $client->company_name ?: __('dashboard.tables.clients.unnamed') }}
                      </a>
                    @else
                      {{ $client->company_name ?: __('dashboard.tables.clients.unnamed') }}
                    @endif
                  </td>
                  <td>{{ $client->contact_name ?: ($client->email ?: __('dashboard.tables.clients.empty_contact')) }}</td>
                  <td>{{ $client->next_follow_up_at ? $client->next_follow_up_at->format('d/m/Y') : __('dashboard.tables.clients.no_reminder') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div class="table-empty">
            <div class="table-empty-icon"><i class="fas fa-users"></i></div>
            <h3>{{ __('dashboard.tables.clients.empty_title') }}</h3>
            <p>{{ __('dashboard.tables.clients.empty_description') }}</p>
          </div>
        @endif
      </section>
    @endif

    @if($hasInvoice)
      <section class="table-wrapper dashboard-table">
        <div class="table-header">
          <div class="table-title">{{ __('dashboard.tables.invoices.title') }}</div>
          <span class="table-count">{{ collect($recentInvoices ?? [])->count() }}</span>
          <div class="table-spacer"></div>
          @if(Route::has('invoices.index'))
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-right"></i> {{ __('dashboard.tables.view_all') }}</a>
          @endif
        </div>
        @if(collect($recentInvoices ?? [])->count())
          <table class="crm-table">
            <thead>
              <tr>
                <th>{{ __('dashboard.tables.invoices.number') }}</th>
                <th>{{ __('dashboard.tables.invoices.client') }}</th>
                <th>{{ __('dashboard.tables.invoices.total') }}</th>
                <th>{{ __('dashboard.tables.invoices.date') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentInvoices as $invoice)
                <tr>
                  <td>
                    @if(Route::has('invoices.show'))
                      <a href="{{ route('invoices.show', $invoice->id) }}" style="text-decoration:none;color:var(--db-accent);font-weight:700;">
                        {{ $invoice->number ?: ('#' . $invoice->id) }}
                      </a>
                    @else
                      {{ $invoice->number ?: ('#' . $invoice->id) }}
                    @endif
                  </td>
                  <td>{{ $invoice->client?->company_name ?: __('dashboard.tables.invoices.empty_value') }}</td>
                  <td>{{ number_format((float) ($invoice->total ?? 0), 2, ',', ' ') }} {{ strtoupper((string) ($invoice->currency ?: ($tenant->currency ?? 'EUR'))) }}</td>
                  <td>{{ optional($invoice->issue_date)->format('d/m/Y') ?: __('dashboard.tables.invoices.empty_value') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div class="table-empty">
            <div class="table-empty-icon"><i class="fas fa-file-invoice"></i></div>
            <h3>{{ __('dashboard.tables.invoices.empty_title') }}</h3>
            <p>{{ __('dashboard.tables.invoices.empty_description') }}</p>
          </div>
        @endif
      </section>
    @endif

    @if($hasStock)
      <section class="table-wrapper dashboard-table">
        <div class="table-header">
          <div class="table-title">{{ __('dashboard.tables.articles.title') }}</div>
          <span class="table-count">{{ collect($criticalArticles ?? [])->count() }}</span>
          <div class="table-spacer"></div>
          @if(Route::has('stock.articles.index'))
            <a href="{{ route('stock.articles.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-right"></i> {{ __('dashboard.tables.view_all') }}</a>
          @endif
        </div>
        @if(collect($criticalArticles ?? [])->count())
          <table class="crm-table">
            <thead>
              <tr>
                <th>{{ __('dashboard.tables.articles.article') }}</th>
                <th>{{ __('dashboard.tables.articles.sku') }}</th>
                <th>{{ __('dashboard.tables.articles.threshold') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($criticalArticles as $article)
                <tr>
                  <td>{{ $article->name ?: __('dashboard.tables.articles.unnamed') }}</td>
                  <td>{{ $article->sku ?: __('dashboard.tables.articles.empty_value') }}</td>
                  <td>{{ number_format((float) ($article->min_stock ?? 0), 2, ',', ' ') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div class="table-empty">
            <div class="table-empty-icon"><i class="fas fa-boxes-stacked"></i></div>
            <h3>{{ __('dashboard.tables.articles.empty_title') }}</h3>
            <p>{{ __('dashboard.tables.articles.empty_description') }}</p>
          </div>
        @endif
      </section>
    @endif

    @if($hasProjects)
      <section class="table-wrapper dashboard-table">
        <div class="table-header">
          <div class="table-title">{{ __('dashboard.tables.upcoming_tasks.title') }}</div>
          <span class="table-count">{{ collect($upcomingTasks ?? [])->count() }}</span>
          <div class="table-spacer"></div>
          @if(Route::has('projects.index'))
            <a href="{{ route('projects.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-right"></i> {{ __('dashboard.tables.view_all') }}</a>
          @endif
        </div>
        @if(collect($upcomingTasks ?? [])->count())
          <table class="crm-table">
            <thead>
              <tr>
                <th>{{ __('dashboard.tables.upcoming_tasks.task') }}</th>
                <th>{{ __('dashboard.tables.upcoming_tasks.project') }}</th>
                <th>{{ __('dashboard.tables.upcoming_tasks.due_date') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($upcomingTasks as $task)
                <tr>
                  <td>{{ \Illuminate\Support\Str::limit((string) $task->title, 40) }}</td>
                  <td>{{ $task->project?->name ?: __('dashboard.tables.upcoming_tasks.undefined_project') }}</td>
                  <td>{{ optional($task->due_date)->format('d/m/Y') ?: __('dashboard.tables.upcoming_tasks.no_date') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div class="table-empty">
            <div class="table-empty-icon"><i class="fas fa-list-check"></i></div>
            <h3>{{ __('dashboard.tables.upcoming_tasks.empty_title') }}</h3>
            <p>{{ __('dashboard.tables.upcoming_tasks.empty_description') }}</p>
          </div>
        @endif
      </section>
    @endif
  </div>
</div>
@endsection

@push('scripts')
  @if(!empty($moduleChart['labels']) || !empty($integrationCards) || $financeChart || $taskChart || $stockChart)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const moduleChart = @json($moduleChart);
        const integrationChart = @json($integrationChart);
        const financeChart = @json($financeChart);
        const taskChart = @json($taskChart);
        const stockChart = @json($stockChart);

        const createGradient = (canvas, from, to) => {
          const context = canvas.getContext('2d');
          const gradient = context.createLinearGradient(0, 0, 0, canvas.height || 280);
          gradient.addColorStop(0, from);
          gradient.addColorStop(1, to);
          return gradient;
        };

        const chartDefaults = {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 900,
            easing: 'easeOutQuart'
          },
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: '#475569',
                font: {
                  family: 'inherit',
                  weight: '700'
                },
                usePointStyle: true,
                padding: 18
              }
            },
            tooltip: {
              backgroundColor: '#081225',
              titleColor: '#f8fafc',
              bodyColor: '#cbd5e1',
              borderColor: 'rgba(255,255,255,.08)',
              borderWidth: 1,
              padding: 12,
              cornerRadius: 14
            }
          }
        };

        if (moduleChart && Array.isArray(moduleChart.labels) && moduleChart.labels.length) {
          const moduleCanvas = document.getElementById('dashboardModuleChart');
          const modulePalette = ['#2563eb', '#7c3aed', '#0f9d58', '#0ea5e9', '#f97316', '#111827'];

          new Chart(moduleCanvas, {
            type: 'bar',
            data: {
              labels: moduleChart.labels,
              datasets: [{
                label: @json(__('dashboard.charts.module_dataset')),
                data: moduleChart.data,
                backgroundColor: moduleChart.data.map((_, index) => modulePalette[index % modulePalette.length]),
                borderRadius: 14,
                borderSkipped: false,
                maxBarThickness: 46
              }]
            },
            options: {
              scales: {
                x: {
                  grid: { display: false },
                  ticks: { color: '#475569', font: { weight: '700' } }
                },
                y: {
                  beginAtZero: true,
                  grid: { color: 'rgba(148, 163, 184, .18)' },
                  ticks: { color: '#64748b' }
                }
              },
              ...chartDefaults
            }
          });
        }

        if (document.getElementById('dashboardIntegrationChart') && integrationChart) {
          new Chart(document.getElementById('dashboardIntegrationChart'), {
            type: 'doughnut',
            data: {
              labels: integrationChart.labels || [@json(__('dashboard.charts.integration.connected')), @json(__('dashboard.charts.integration.reconnect')), @json(__('dashboard.charts.integration.installed'))],
              datasets: [{
                data: integrationChart.data,
                backgroundColor: ['#16a34a', '#ea580c', '#2563eb'],
                hoverOffset: 8,
                borderWidth: 0,
                spacing: 4
              }]
            },
            options: {
              ...chartDefaults,
              cutout: '70%',
              plugins: {
                ...(chartDefaults.plugins || {}),
                legend: {
                  ...((chartDefaults.plugins && chartDefaults.plugins.legend) || {}),
                  display: false
                }
              }
            }
          });
        }

        if (document.getElementById('dashboardFinanceChart') && financeChart) {
          const financeCanvas = document.getElementById('dashboardFinanceChart');
          const invoiceFill = createGradient(financeCanvas, 'rgba(37, 99, 235, .30)', 'rgba(37, 99, 235, .02)');
          const paymentFill = createGradient(financeCanvas, 'rgba(15, 157, 88, .28)', 'rgba(15, 157, 88, .02)');

          new Chart(financeCanvas, {
            type: 'line',
            data: {
              labels: financeChart.labels,
              datasets: [
                {
                  label: @json(__('dashboard.charts.finance.invoices')),
                  data: financeChart.invoiceData,
                  borderColor: '#2563eb',
                  backgroundColor: invoiceFill,
                  tension: .38,
                  fill: true,
                  pointRadius: 4,
                  pointHoverRadius: 6,
                  pointBackgroundColor: '#2563eb',
                  pointBorderWidth: 0
                },
                {
                  label: @json(__('dashboard.charts.finance.payments')),
                  data: financeChart.paymentData,
                  borderColor: '#0f9d58',
                  backgroundColor: paymentFill,
                  tension: .38,
                  fill: true,
                  pointRadius: 4,
                  pointHoverRadius: 6,
                  pointBackgroundColor: '#0f9d58',
                  pointBorderWidth: 0
                }
              ]
            },
            options: {
              interaction: {
                mode: 'index',
                intersect: false
              },
              scales: {
                x: {
                  grid: { display: false },
                  ticks: { color: '#475569', font: { weight: '700' } }
                },
                y: {
                  beginAtZero: true,
                  grid: { color: 'rgba(148, 163, 184, .18)' },
                  ticks: { color: '#64748b' }
                }
              },
              ...chartDefaults
            }
          });
        }

        if (document.getElementById('dashboardTaskChart') && taskChart) {
          new Chart(document.getElementById('dashboardTaskChart'), {
            type: 'doughnut',
            data: {
              labels: taskChart.labels || [@json(__('dashboard.charts.tasks.todo')), @json(__('dashboard.charts.tasks.in_progress')), @json(__('dashboard.charts.tasks.review')), @json(__('dashboard.charts.tasks.done'))],
              datasets: [{
                data: taskChart.data,
                backgroundColor: ['#94a3b8', '#2563eb', '#f59e0b', '#16a34a'],
                borderWidth: 0,
                hoverOffset: 8,
                spacing: 4
              }]
            },
            options: {
              cutout: '68%',
              ...chartDefaults
            }
          });
        }

        if (document.getElementById('dashboardStockChart') && stockChart) {
          new Chart(document.getElementById('dashboardStockChart'), {
            type: 'doughnut',
            data: {
              labels: stockChart.labels || [@json(__('dashboard.charts.stock.critical')), @json(__('dashboard.charts.stock.healthy'))],
              datasets: [{
                data: stockChart.data,
                backgroundColor: ['#ea580c', '#0f9d58'],
                borderWidth: 0,
                hoverOffset: 8,
                spacing: 4
              }]
            },
            options: {
              cutout: '68%',
              ...chartDefaults
            }
          });
        }
      });
    </script>
  @endif
@endpush
