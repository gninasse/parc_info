<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">

  <div class="sidebar-brand">
    <a href="{{ url('/') }}" class="brand-link d-flex align-items-center gap-2 px-3 py-2">
      <img src="{{ asset('images/chuyo_icon.png') }}" alt="CHU-YO"
           class="brand-image" style="width: 36px; height: 36px; object-fit: contain; border-radius: 8px;" />
      <span class="brand-text fw-semibold">CHU-YO | Stocks</span>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column"
          data-lte-toggle="treeview"
          role="navigation"
          aria-label="Navigation principale"
          data-accordion="false"
          id="navigation">

        {{-- Une entrée de menu non autorisée n'est pas rendue. --}}

        @can('stock.dashboard.view')
        <li class="nav-item">
          <a href="{{ route('stock.dashboard.index') }}"
             class="nav-link {{ request()->routeIs('stock.dashboard.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Tableau de bord</p>
          </a>
        </li>
        @endcan

        <li class="nav-header">GESTION DES STOCKS</li>

        @can('stock.magasins.view')
        <li class="nav-item">
          <a href="{{ route('stock.magasins.index') }}"
             class="nav-link {{ request()->routeIs('stock.magasins.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-shop"></i>
            <p>Magasins</p>
          </a>
        </li>
        @endcan

        @can('stock.articles.view')
        <li class="nav-item">
          <a href="{{ route('stock.articles.index') }}"
             class="nav-link {{ request()->routeIs('stock.articles.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-boxes"></i>
            <p>Stock par article</p>
          </a>
        </li>
        @endcan

        <li class="nav-header">MOUVEMENTS</li>

        @can('stock.entrees.view')
        <li class="nav-item">
          <a href="{{ route('stock.entrees.index') }}"
             class="nav-link {{ request()->routeIs('stock.entrees.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-box-arrow-in-down"></i>
            <p>Entrées</p>
          </a>
        </li>
        @endcan

        @if (Route::has('stock.sorties.index'))
        @can('stock.sorties.view')
        <li class="nav-item">
          <a href="{{ route('stock.sorties.index') }}"
             class="nav-link {{ request()->routeIs('stock.sorties.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-box-arrow-up"></i>
            <p>Sorties</p>
          </a>
        </li>
        @endcan
        @endif

        @if (Route::has('stock.transferts.index'))
        @can('stock.transferts.view')
        <li class="nav-item">
          <a href="{{ route('stock.transferts.index') }}"
             class="nav-link {{ request()->routeIs('stock.transferts.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-arrow-left-right"></i>
            <p>Transferts</p>
          </a>
        </li>
        @endcan
        @endif

        @if (Route::has('stock.inventaires.index'))
        <li class="nav-header">CONTRÔLE</li>

        @can('stock.inventaires.view')
        <li class="nav-item">
          <a href="{{ route('stock.inventaires.index') }}"
             class="nav-link {{ request()->routeIs('stock.inventaires.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-clipboard-check"></i>
            <p>Inventaires</p>
          </a>
        </li>
        @endcan
        @endif

        @if (Route::has('stock.valorisation.index'))
        @can('stock.valorisation.view')
        <li class="nav-item">
          <a href="{{ route('stock.valorisation.index') }}"
             class="nav-link {{ request()->routeIs('stock.valorisation.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-coin"></i>
            <p>Valorisation</p>
          </a>
        </li>
        @endcan
        @endif

        @if (Route::has('stock.rapports.index'))
        @can('stock.rapports.view')
        <li class="nav-item">
          <a href="{{ route('stock.rapports.index') }}"
             class="nav-link {{ request()->routeIs('stock.rapports.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-file-earmark-bar-graph"></i>
            <p>Rapports</p>
          </a>
        </li>
        @endcan
        @endif

      </ul>
    </nav>
  </div>
</aside>
<!--end::Sidebar-->
