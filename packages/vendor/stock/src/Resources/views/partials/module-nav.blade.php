@php
  $stockNavItems = [
      ['route' => 'stock.articles.index', 'label' => 'Articles', 'icon' => 'fas fa-boxes-stacked'],
      ['route' => 'stock.suppliers.index', 'label' => 'Fournisseurs', 'icon' => 'fas fa-building'],
      ['route' => 'stock.orders.index', 'label' => 'Commandes', 'icon' => 'fas fa-truck-loading'],
      ['route' => 'stock.delivery-notes.index', 'label' => 'Bons de livraison', 'icon' => 'fas fa-truck-ramp-box'],
      ['route' => 'stock.movements.index', 'label' => 'Historique stock', 'icon' => 'fas fa-arrows-rotate'],
  ];
@endphp

<div class="module-subnav" style="display:flex;gap:10px;flex-wrap:wrap;margin:0 0 18px;">
  @foreach($stockNavItems as $item)
    @php
      $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route']));
    @endphp
    <a
      href="{{ route($item['route']) }}"
      class="btn {{ $active ? 'btn-primary' : 'btn-secondary' }}"
      style="{{ $active ? '' : 'background:#fff;' }}"
    >
      <i class="{{ $item['icon'] }}"></i> {{ $item['label'] }}
    </a>
  @endforeach
</div>
