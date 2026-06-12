      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
              </a>
            </li>
          </ul>
          <!--end::Start Navbar Links-->
          <!--begin::Center Navbar Links-->
          <ul class="navbar-nav mx-auto">
            {{-- Dashboard --}}
            @can('parcinfo.dashboard.view')
            <li class="nav-item">
              <a class="nav-link d-flex align-items-center {{ request()->routeIs('parc-info.dashboard') ? 'active fw-bold text-primary' : '' }}" href="{{ route('parc-info.dashboard') }}">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
              </a>
            </li>
            @endcan

            {{-- Actifs & Logiciels --}}
            @canany([
                'parcinfo.ordinateurs.index',
                'parcinfo.serveurs.index',
                'parcinfo.mobiles.index',
                'parcinfo.logiciels.index',
                'parcinfo.fournisseurs.index',
                'parcinfo.licences.index'
            ])
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center {{ request()->routeIs('parc-info.ordinateurs.*', 'parc-info.serveurs.*', 'parc-info.serveurs-virtuels.*', 'parc-info.mobiles.*', 'parc-info.logiciels.*', 'parc-info.fournisseurs.*', 'parc-info.licences.*') ? 'active fw-bold' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-pc-display-horizontal me-1"></i> Actifs & Logiciels
              </a>
              <ul class="dropdown-menu dropdown-menu-start shadow border-0" style="min-width: 220px;">
                @canany(['parcinfo.ordinateurs.index', 'parcinfo.serveurs.index', 'parcinfo.mobiles.index'])
                <li><h6 class="dropdown-header fw-bold text-uppercase small text-muted">Gestion des Actifs</h6></li>
                @can('parcinfo.ordinateurs.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.ordinateurs.*') ? 'active' : '' }}" href="{{ route('parc-info.ordinateurs.index') }}"><i class="bi bi-pc-display me-2 text-primary"></i> Ordinateurs</a></li>
                @endcan
                @can('parcinfo.serveurs.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.serveurs.*') ? 'active' : '' }}" href="{{ route('parc-info.serveurs.index') }}"><i class="bi bi-server me-2 text-primary"></i> Serveurs Physiques</a></li>
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.serveurs-virtuels.*') ? 'active' : '' }}" href="{{ route('parc-info.serveurs-virtuels.index') }}"><i class="bi bi-cpu me-2 text-primary"></i> Machines Virtuelles</a></li>
                @endcan
                @can('parcinfo.mobiles.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.mobiles.*') && !request()->is('*telephonie*') ? 'active' : '' }}" href="{{ route('parc-info.mobiles.index') }}"><i class="bi bi-phone-vibrate me-2 text-primary"></i> Tablettes & Mobiles</a></li>
                @endcan
                @endcanany

                @canany(['parcinfo.logiciels.index', 'parcinfo.fournisseurs.index', 'parcinfo.licences.index'])
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header fw-bold text-uppercase small text-muted">Logiciels & Licences</h6></li>
                @can('parcinfo.logiciels.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.logiciels.*') ? 'active' : '' }}" href="{{ route('parc-info.logiciels.index') }}"><i class="bi bi-compact-disc me-2 text-info"></i> Catalogue Logiciels</a></li>
                @endcan
                @can('parcinfo.fournisseurs.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.fournisseurs.*') ? 'active' : '' }}" href="{{ route('parc-info.fournisseurs.index') }}"><i class="bi bi-truck me-2 text-info"></i> Fournisseurs</a></li>
                @endcan
                @can('parcinfo.licences.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.licences.*') ? 'active' : '' }}" href="{{ route('parc-info.licences.index') }}"><i class="bi bi-file-lock me-2 text-info"></i> Licences</a></li>
                @endcan
                @endcanany
              </ul>
            </li>
            @endcanany

            {{-- Consommables --}}
            @can('parcinfo.consommables.index')
            <li class="nav-item">
              <a class="nav-link d-flex align-items-center {{ request()->routeIs('parc-info.consommables.*') ? 'active fw-bold text-primary' : '' }}" href="{{ route('parc-info.consommables.index') }}">
                <i class="bi bi-cart-check me-1"></i> Stock Consommables
              </a>
            </li>
            @endcan

            {{-- Réseau & Infra --}}
            @canany([
                'parcinfo.switches.index',
                'parcinfo.routeurs.index',
                'parcinfo.wifi.index',
                'parcinfo.parefeux.index',
                'parcinfo.onduleurs.index',
                'parcinfo.racks.index',
                'parcinfo.brassage.index'
            ])
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center {{ request()->routeIs('parc-info.switches.*', 'parc-info.routeurs.*', 'parc-info.wifi.*', 'parc-info.parefeux.*', 'parc-info.onduleurs.*', 'parc-info.racks.*', 'parc-info.brassage.*') ? 'active fw-bold' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-diagram-3 me-1"></i> Réseau & Infra
              </a>
              <ul class="dropdown-menu dropdown-menu-start shadow border-0" style="min-width: 220px;">
                @canany(['parcinfo.switches.index', 'parcinfo.routeurs.index', 'parcinfo.wifi.index', 'parcinfo.parefeux.index'])
                <li><h6 class="dropdown-header fw-bold text-uppercase small text-muted">Équipements Réseau</h6></li>
                @can('parcinfo.switches.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.switches.*') ? 'active' : '' }}" href="{{ route('parc-info.switches.index') }}"><i class="bi bi-hdd-network me-2 text-info"></i> Switches</a></li>
                @endcan
                @can('parcinfo.routeurs.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.routeurs.*') ? 'active' : '' }}" href="{{ route('parc-info.routeurs.index') }}"><i class="bi bi-router me-2 text-info"></i> Routeurs</a></li>
                @endcan
                @can('parcinfo.wifi.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.wifi.*') ? 'active' : '' }}" href="{{ route('parc-info.wifi.index') }}"><i class="bi bi-wifi me-2 text-info"></i> Points d'accès WiFi</a></li>
                @endcan
                @can('parcinfo.parefeux.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.parefeux.*') ? 'active' : '' }}" href="{{ route('parc-info.parefeux.index') }}"><i class="bi bi-shield-shaded me-2 text-info"></i> Pare-feux</a></li>
                @endcan
                @endcanany

                @canany(['parcinfo.onduleurs.index', 'parcinfo.racks.index', 'parcinfo.brassage.index'])
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header fw-bold text-uppercase small text-muted">Infrastructure</h6></li>
                @can('parcinfo.onduleurs.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.onduleurs.*') ? 'active' : '' }}" href="{{ route('parc-info.onduleurs.index') }}"><i class="bi bi-lightning-charge me-2 text-warning"></i> Onduleurs</a></li>
                @endcan
                @can('parcinfo.racks.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.racks.*') ? 'active' : '' }}" href="{{ route('parc-info.racks.index') }}"><i class="bi bi-grid-3x3-gap me-2 text-warning"></i> Baies & Racks</a></li>
                @endcan
                @can('parcinfo.brassage.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.brassage.*') ? 'active' : '' }}" href="{{ route('parc-info.brassage.index') }}"><i class="bi bi-ethernet me-2 text-warning"></i> Brassage</a></li>
                @endcan
                @endcanany
              </ul>
            </li>
            @endcanany

            {{-- Périphériques & Comm --}}
            @canany([
                'parcinfo.imprimantes.index',
                'parcinfo.scanners.index',
                'parcinfo.cameras.index',
                'parcinfo.telephonie.index',
                'parcinfo.terminaux-ip.index'
            ])
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center {{ request()->routeIs('parc-info.imprimantes.*', 'parc-info.scanners.*', 'parc-info.cameras.*', 'parc-info.telephonie.*', 'parc-info.terminaux-ip.*') ? 'active fw-bold' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-printer me-1"></i> Périphériques & Comm
              </a>
              <ul class="dropdown-menu dropdown-menu-start shadow border-0" style="min-width: 220px;">
                @canany(['parcinfo.imprimantes.index', 'parcinfo.scanners.index', 'parcinfo.cameras.index'])
                <li><h6 class="dropdown-header fw-bold text-uppercase small text-muted">Impression & Sécurité</h6></li>
                @can('parcinfo.imprimantes.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.imprimantes.*') ? 'active' : '' }}" href="{{ route('parc-info.imprimantes.index') }}"><i class="bi bi-printer me-2 text-success"></i> Imprimantes</a></li>
                @endcan
                @can('parcinfo.scanners.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.scanners.*') ? 'active' : '' }}" href="{{ route('parc-info.scanners.index') }}"><i class="bi bi-upc-scan me-2 text-success"></i> Scanners</a></li>
                @endcan
                @can('parcinfo.cameras.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.cameras.*') ? 'active' : '' }}" href="{{ route('parc-info.cameras.index') }}"><i class="bi bi-camera-video me-2 text-success"></i> Caméras IP</a></li>
                @endcan
                @endcanany

                @canany(['parcinfo.telephonie.index', 'parcinfo.terminaux-ip.index', 'parcinfo.mobiles.index'])
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header fw-bold text-uppercase small text-muted">Téléphonie & Comm</h6></li>
                @can('parcinfo.telephonie.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.telephonie.*') ? 'active' : '' }}" href="{{ route('parc-info.telephonie.index') }}"><i class="bi bi-telephone me-2 text-primary"></i> Téléphones fixes</a></li>
                @endcan 
                @can('parcinfo.terminaux-ip.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.terminaux-ip.*') ? 'active' : '' }}" href="{{ route('parc-info.terminaux-ip.index') }}"><i class="bi bi-headset me-2 text-primary"></i> Terminaux IP</a></li>
                @endcan
                @endcanany
              </ul>
            </li>
            @endcanany

            {{-- Référentiels --}}
            @canany([
                'parc-info.referentiels.types-cpus.index',
                'parc-info.referentiels.types-disques.index',
                'parc-info.referentiels.types-os.index',
                'parc-info.referentiels.types-rams.index',
                'parc-info.referentiels.marques.index',
                'parc-info.referentiels.types-imprimantes.index',
                'parc-info.referentiels.types-mobiles.index',
                'parc-info.referentiels.types-reseaux.index',
                'parc-info.referentiels.types-infrastructures.index',
                'parc-info.referentiels.types-licences.index',
                'parc-info.referentiels.types-consommables.index',
                'parc-info.referentiels.editeurs.index'
            ])
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.*') ? 'active fw-bold' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-gear me-1"></i> Référentiels
              </a>
              <ul class="dropdown-menu dropdown-menu-start shadow border-0" style="max-height: 400px; overflow-y: auto; min-width: 240px;">
                {{-- Group 1: Types Matériels --}}
                @canany([
                    'parc-info.referentiels.types-cpus.index',
                    'parc-info.referentiels.types-disques.index',
                    'parc-info.referentiels.types-os.index',
                    'parc-info.referentiels.types-rams.index',
                    'parc-info.referentiels.marques.index'
                ])
                <li><h6 class="dropdown-header fw-bold text-uppercase small text-muted">Types Matériels</h6></li>
                @can('parc-info.referentiels.types-cpus.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-cpus.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-cpus.index') }}"><i class="bi bi-cpu me-2 text-primary"></i> Types CPU</a></li>
                @endcan
                @can('parc-info.referentiels.types-disques.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-disques.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-disques.index') }}"><i class="bi bi-hdd me-2 text-primary"></i> Types Disques</a></li>
                @endcan
                @can('parc-info.referentiels.types-os.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-os.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-os.index') }}"><i class="bi bi-windows me-2 text-primary"></i> Types OS</a></li>
                @endcan
                @can('parc-info.referentiels.types-rams.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-rams.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-rams.index') }}"><i class="bi bi-memory me-2 text-primary"></i> Types RAM</a></li>
                @endcan
                @can('parc-info.referentiels.marques.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.marques.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.marques.index') }}"><i class="bi bi-tag me-2 text-primary"></i> Marques</a></li>
                @endcan
                @endcanany

                <li><hr class="dropdown-divider"></li>

                {{-- Group 2: Types Équipements --}}
                @canany([
                    'parc-info.referentiels.types-imprimantes.index',
                    'parc-info.referentiels.types-mobiles.index',
                    'parc-info.referentiels.types-reseaux.index',
                    'parc-info.referentiels.types-infrastructures.index'
                ])
                <li><h6 class="dropdown-header fw-bold text-uppercase small text-muted">Types Équipements</h6></li>
                @can('parc-info.referentiels.types-imprimantes.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-imprimantes.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-imprimantes.index') }}"><i class="bi bi-printer me-2 text-info"></i> Types Imprimantes</a></li>
                @endcan
                @can('parc-info.referentiels.types-mobiles.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-mobiles.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-mobiles.index') }}"><i class="bi bi-phone me-2 text-info"></i> Types Mobiles</a></li>
                @endcan
                @can('parc-info.referentiels.types-reseaux.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-reseaux.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-reseaux.index') }}"><i class="bi bi-diagram-3 me-2 text-info"></i> Types Réseau</a></li>
                @endcan
                @can('parc-info.referentiels.types-infrastructures.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-infrastructures.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-infrastructures.index') }}"><i class="bi bi-building me-2 text-info"></i> Types Infrastructure</a></li>
                @endcan
                @endcanany

                <li><hr class="dropdown-divider"></li>

                {{-- Group 3: Autres --}}
                @canany([
                    'parc-info.referentiels.types-licences.index',
                    'parc-info.referentiels.types-consommables.index',
                    'parc-info.referentiels.editeurs.index'
                ])
                <li><h6 class="dropdown-header fw-bold text-uppercase small text-muted">Autres Référentiels</h6></li>
                @can('parc-info.referentiels.types-licences.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-licences.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-licences.index') }}"><i class="bi bi-file-lock me-2 text-success"></i> Types Licences</a></li>
                @endcan
                @can('parc-info.referentiels.types-consommables.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.types-consommables.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.types-consommables.index') }}"><i class="bi bi-box2 me-2 text-success"></i> Types Consommables</a></li>
                @endcan
                @can('parc-info.referentiels.editeurs.index')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.referentiels.editeurs.*') ? 'active' : '' }}" href="{{ route('parc-info.referentiels.editeurs.index') }}"><i class="bi bi-building me-2 text-success"></i> Éditeurs</a></li>
                @endcan
                @endcanany
              </ul>
            </li>
            @endcanany

            {{-- Analyse --}}
            @canany([
                'parc-info.analyse.etats.view',
                'parc-info.analyse.statistiques.view'
            ])
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center {{ request()->routeIs('parc-info.analyse.*') ? 'active fw-bold' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bar-chart me-1"></i> Analyse
              </a>
              <ul class="dropdown-menu dropdown-menu-start shadow border-0" style="min-width: 200px;">
                @can('parc-info.analyse.etats.view')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.analyse.etats.index') ? 'active' : '' }}" href="{{ route('parc-info.analyse.etats.index') }}"><i class="bi bi-circle-fill me-2 text-warning" style="font-size: 0.6rem;"></i> États des Équipements</a></li>
                @endcan
                @can('parc-info.analyse.statistiques.view')
                <li><a class="dropdown-item d-flex align-items-center {{ request()->routeIs('parc-info.analyse.statistiques.index') ? 'active' : '' }}" href="{{ route('parc-info.analyse.statistiques.index') }}"><i class="bi bi-graph-up me-2 text-success"></i> Statistiques</a></li>
                @endcan
              </ul>
            </li>
            @endcanany
          </ul>
          <!--end::Center Navbar Links-->
          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">
            <!--begin::Navbar Search-->
            <li class="nav-item">
              <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                <i class="bi bi-search"></i>
              </a>
            </li>
            <!--end::Navbar Search--> 
            <!--end::Notifications Dropdown Menu-->
            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
              </a>
            </li>
            <!--end::Fullscreen Toggle-->
            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img
                  src="{{ Auth::user()->avatar_url }}"
                  class="user-image rounded-circle shadow"
                  alt="User Image"
                />
                <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <!--begin::User Image-->
                <li class="user-header text-bg-primary">
                  <img
                    src="{{ Auth::user()->avatar_url }}"
                    class="rounded-circle shadow"
                    alt="User Image"
                  />
                  <p>
                    {{ Auth::user()->name }}
                    <small>Actif depuis : {{ Auth::user()->created_at->locale('fr')->translatedFormat('d F Y')}}</small>
                  </p>
                </li>
                <!--end::User Image-->
                
                <!--begin::Menu Footer-->
                <li class="user-footer">
                  <a href="{{ route('cores.profile') }}" class="btn btn-default btn-flat">Profile</a>
                  <a href="#" class="btn btn-default btn-flat float-end"
                     onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                     Se deconnecter
                  </a>
                  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                      @csrf
                  </form>
                </li>
                <!--end::Menu Footer-->
              </ul>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->
