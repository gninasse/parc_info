      <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <!--begin::Brand Link-->
          <a href="{{ url('/') }}" class="brand-link d-flex align-items-center gap-2 px-3 py-2">
            <img
              src="{{ asset('images/chuyo_icon.png') }}"
              alt="CHU-YO Icon"
              class="brand-image"
              style="width: 36px; height: 36px; object-fit: contain; border-radius: 8px;"
            />
            <span class="brand-text fw-semibold">CHU-YO | STOCK</span>
          </a>
          <!--end::Brand Link-->
        </div>
        <!--end::Sidebar Brand-->
        <!--begin::Sidebar Wrapper-->
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul
              class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview"
              role="navigation"
              aria-label="Main navigation"
              data-accordion="false"
              id="navigation"
            >
              @php
                // Ordre imposé par UX §0.1. Les entrées dont la page n'est pas
                // encore développée (Route::has = false) restent masquées.
                $itemsStock = [
                    ['label' => 'Tableau de bord', 'icon' => 'bi bi-speedometer2', 'route' => 'stock.dashboard', 'actif' => 'stock.dashboard', 'permission' => 'stock.dashboard.view'],
                    ['label' => 'État des stocks', 'icon' => 'bi bi-clipboard-data', 'route' => 'stock.niveaux.index', 'actif' => 'stock.niveaux.*', 'permission' => 'stock.niveaux.index'],
                    ['label' => 'Entrées', 'icon' => 'bi bi-box-arrow-in-down', 'route' => 'stock.entrees.index', 'actif' => 'stock.entrees.*', 'permission' => 'stock.entrees.index'],
                    ['label' => 'Sorties', 'icon' => 'bi bi-box-arrow-up', 'route' => 'stock.sorties.index', 'actif' => 'stock.sorties.*', 'permission' => 'stock.sorties.index'],
                    ['label' => 'Transferts', 'icon' => 'bi bi-arrow-left-right', 'route' => 'stock.transferts.index', 'actif' => 'stock.transferts.*', 'permission' => 'stock.transferts.index'],
                    ['label' => 'Inventaires', 'icon' => 'bi bi-clipboard-check', 'route' => 'stock.inventaires.index', 'actif' => 'stock.inventaires.*', 'permission' => 'stock.inventaires.index'],
                    ['label' => 'Mouvements', 'icon' => 'bi bi-clock-history', 'route' => 'stock.mouvements.index', 'actif' => 'stock.mouvements.*', 'permission' => 'stock.mouvements.index'],
                    ['label' => 'Statistiques', 'icon' => 'bi bi-bar-chart-line', 'route' => 'stock.statistiques.index', 'actif' => 'stock.statistiques.*', 'permission' => 'stock.rapports.view'],
                    ['label' => 'États & rapports', 'icon' => 'bi bi-graph-up', 'route' => 'stock.rapports.index', 'actif' => 'stock.rapports.*', 'permission' => 'stock.rapports.view'],
                ];
                $itemsReferentiels = [
                    ['label' => 'Magasins', 'icon' => 'bi bi-shop', 'route' => 'stock.magasins.index', 'actif' => 'stock.magasins.*', 'permission' => 'stock.magasins.index'],
                ];
              @endphp

              @foreach($itemsStock as $item)
                @if(Route::has($item['route']))
                  @can($item['permission'])
                    <li class="nav-item">
                      <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['actif']) ? 'active' : '' }}">
                        <i class="nav-icon {{ $item['icon'] }}"></i>
                        <p>{{ $item['label'] }}</p>
                      </a>
                    </li>
                  @endcan
                @endif
              @endforeach

              @php
                $referentielsVisibles = collect($itemsReferentiels)
                    ->filter(fn ($item) => Route::has($item['route']) && auth()->user()->can($item['permission']));
              @endphp
              @if($referentielsVisibles->isNotEmpty())
                <li class="nav-header">RÉFÉRENTIELS</li>
                @foreach($referentielsVisibles as $item)
                  <li class="nav-item">
                    <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['actif']) ? 'active' : '' }}">
                      <i class="nav-icon {{ $item['icon'] }}"></i>
                      <p>{{ $item['label'] }}</p>
                    </a>
                  </li>
                @endforeach
              @endif

              @include('core::partials.sidebar-modules', ['moduleCourant' => 'stock'])

              <li class="nav-item mt-4 border-top border-secondary pt-3">
                <a href="{{ url('/') }}" class="nav-link text-warning">
                  <i class="nav-icon bi bi-house-door"></i>
                  <p>ACCUEIL GÉNÉRAL</p>
                </a>
              </li>
            </ul>
            <!--end::Sidebar Menu-->
          </nav>
        </div>
        <!--end::Sidebar Wrapper-->
      </aside>
      <!--end::Sidebar-->
