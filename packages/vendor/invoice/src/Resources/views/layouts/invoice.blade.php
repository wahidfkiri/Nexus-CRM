<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Facturation') — {{ config('app.name') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    {{-- CRM Core CSS (from client module) --}}
    @if(file_exists(public_path('vendor/client/css/crm.css')))
    <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
    @endif

    {{-- Invoice module CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/invoice/css/invoice.css') }}">

    @stack('styles')
</head>
<body>
<div class="crm-layout">

    {{-- Sidebar --}}
    <aside class="crm-sidebar">
        <div style="padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.08)">
            <a href="/dashboard" style="display:flex;align-items:center;gap:10px;text-decoration:none">
                <div style="width:32px;height:32px;background:var(--c-accent);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px">C</div>
                <span style="font-family:var(--ff-display);font-weight:700;font-size:16px;color:#fff">CRM SaaS</span>
            </a>
        </div>

        <nav style="flex:1;padding:16px 12px;overflow-y:auto">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.3);padding:8px 12px;margin-bottom:4px">Principal</div>

            @php
            $navItems = [
                ['icon'=>'🏠', 'label'=>'Tableau de bord', 'route'=>'/dashboard'],
                ['icon'=>'👥', 'label'=>'Clients', 'route'=>route('clients.index')],
                ['icon'=>'📄', 'label'=>'Factures', 'route'=>route('invoices.index'), 'active'=>true],
                ['icon'=>'📝', 'label'=>'Devis', 'route'=>route('invoices.quotes.index')],
            ];
            @endphp

            @foreach($navItems as $item)
            @php $isActive = request()->is(ltrim($item['route'], '/') . '*') || ($item['active'] ?? false); @endphp
            <a href="{{ $item['route'] }}" style="
                display:flex;align-items:center;gap:10px;
                padding:9px 12px;border-radius:8px;
                color:{{ $isActive ? '#fff' : 'rgba(255,255,255,.6)' }};
                background:{{ $isActive ? 'rgba(255,255,255,.1)' : 'transparent' }};
                text-decoration:none;font-size:13.5px;font-weight:{{ $isActive ? '600' : '400' }};
                margin-bottom:2px;transition:all 200ms;
            " onmouseover="if(!{{ $isActive ? 'true' : 'false' }})this.style.background='rgba(255,255,255,.06)'"
               onmouseout="if(!{{ $isActive ? 'true' : 'false' }})this.style.background='transparent'">
                <span>{{ $item['icon'] }}</span>
                {{ $item['label'] }}
            </a>
            @endforeach
        </nav>

        {{-- User --}}
        <div style="padding:16px 20px;border-top:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:10px">
            <div style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
            </div>
            <div style="min-width:0">
                <div style="font-size:13px;font-weight:600;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    {{ auth()->user()->name ?? 'Utilisateur' }}
                </div>
                <div style="font-size:11px;color:rgba(255,255,255,.4);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    {{ auth()->user()->email ?? '' }}
                </div>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <main class="crm-main">
        {{-- Topbar --}}
        <header class="crm-topbar">
            <div style="flex:1">
                <nav style="font-size:13px;color:var(--c-ink-40);display:flex;align-items:center;gap:6px">
                    <a href="/dashboard" style="color:var(--c-ink-40);text-decoration:none">Accueil</a>
                    <span>›</span>
                    <span style="color:var(--c-ink)">@yield('title','Facturation')</span>
                </nav>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
                <span style="font-size:12px;color:var(--c-ink-40)">
                    {{ auth()->user()->tenant->name ?? config('app.name') }}
                </span>
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:var(--c-ink-40);font-size:13px;cursor:pointer;padding:6px 10px;border-radius:6px;font-family:var(--ff-body)">
                        Déconnexion
                    </button>
                </form>
            </div>
        </header>

        {{-- Content --}}
        <div class="crm-content">
            @yield('content')
        </div>
    </main>

</div>

{{-- Toast container --}}
<div class="toast-container"></div>

{{-- Core JS --}}
@if(file_exists(public_path('vendor/client/js/crm.js')))
<script src="{{ asset('vendor/client/js/crm.js') }}"></script>
@endif
<script src="{{ asset('vendor/invoice/js/invoice.js') }}"></script>

@stack('scripts')
</body>
</html>
