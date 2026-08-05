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
            <span class="brand-text fw-semibold">CHU-YO | ACHAT</span>
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
                // Ordre imposé par SPEC_UX §0.1. Les entrées dont la page n'est
                // pas encore développée (Route::has = false) restent masquées :
                // pas de lien mort dans la navigation.
                $itemsAchat = [
                    ['label' => 'Tableau de bord', 'icon' => 'bi bi-speedometer2', 'route' => 'achat.dashboard', 'actif' => 'achat.dashboard', 'permission' => 'achat.dashboard.view'],
                    ['label' => 'Bons de commande', 'icon' => 'bi bi-card-checklist', 'route' => 'achat.bons-commande.index', 'actif' => 'achat.bons-commande.*', 'permission' => 'achat.bons_commande.index'],
                    ['label' => 'Reliquats', 'icon' => 'bi bi-hourglass-split', 'route' => 'achat.reliquats.index', 'actif' => 'achat.reliquats.*', 'permission' => 'achat.reliquats.index'],
                    ['label' => 'Rapports', 'icon' => 'bi bi-graph-up', 'route' => 'achat.rapports.index', 'actif' => 'achat.rapports.*', 'permission' => 'achat.rapports.view'],
                ];
                $itemsReferentiels = [
                    ['label' => 'Administration', 'icon' => 'bi bi-sliders', 'route' => 'achat.administration.index', 'actif' => 'achat.administration.*', 'permission' => 'achat.administration.manage'],
                ];
              @endphp

              @foreach($itemsAchat as $item)
                @if(Route::has($item['route']))
                  @can($item['permission'])
                    <li class="nav-item">
                      <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['actif']) ? 'active' : '' }}">
                        <i class="nav-icon {{ $item['icon'] }}"></i>
                        <p>
                          {{ $item['label'] }}
                          {{-- Badge « à valider » sur l'entrée Bons de commande (SPEC_UX §0.1) --}}
                          @if($item['route'] === 'achat.bons-commande.index')
                            @can('achat.bons_commande.valider')
                              @php
                                  $aValider = \Modules\Achat\Models\BonCommande::query()->aValider()->count();
                              @endphp
                              @if($aValider > 0)
                                <span class="nav-badge badge text-bg-danger me-3">{{ $aValider }}</span>
                              @endif
                            @endcan
                          @endif
                        </p>
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

              @include('core::partials.sidebar-modules', ['moduleCourant' => 'achat'])

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
