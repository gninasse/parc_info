<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>@yield('title', 'CHU-YO | Gestion des Stocks')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/chuyo_icon.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('images/chuyo_icon.png') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    
    <!--begin::Fonts-->
    <link rel="stylesheet" href="{{ asset('plugins/source-sans-3/index.css') }}" />
    <!--begin::Third Party Plugins-->
    <link rel="stylesheet" href="{{ asset('plugins/overlayscrollbars/styles/overlayscrollbars.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/bootstrap-icons/font/bootstrap-icons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2-bootstrap-5-theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/tools/tools.css') }}" />
    
    <!-- Bootstrap Table -->
    <link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}" />

    <style>
      /* Styles personnalisés pour le module stock */
      .app-sidebar[data-bs-theme=dark] {
          background-color: #1a252f !important;
      }
      .sidebar-brand {
          background-color: #141c24 !important;
          border-bottom: 1px solid #2c3e50;
      }
      .nav-link.active {
          background-color: #34495e !important;
          color: #fff !important;
      }
    </style>

    @stack('css')
  </head>

  <body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="app-wrapper">
      
      <!-- Navbar -->
      @include('core::layouts.partials.navbar')

      <!-- Sidebar Dédiée au Stock -->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
          <a href="{{ route('stock.dashboard.index') }}" class="brand-link d-flex align-items-center gap-2 px-3 py-2">
            <img src="{{ asset('images/chuyo_icon.png') }}" alt="CHU-YO Icon" class="brand-image" style="width: 36px; height: 36px; object-fit: contain; border-radius: 8px;" />
            <span class="brand-text fw-semibold text-white">CHU-YO | Stocks</span>
          </a>
        </div>
        
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" data-accordion="false">
              
              <!-- Dashboard -->
              <li class="nav-item">
                <a href="{{ route('stock.dashboard.index') }}" class="nav-link {{ request()->routeIs('stock.dashboard.index') ? 'active' : '' }}">
                  <i class="nav-icon fas fa-chart-line text-info"></i>
                  <p>Tableau de Bord</p>
                </a>
              </li>

              <!-- Magasins -->
              <li class="nav-item">
                <a href="{{ route('stock.magasins.index') }}" class="nav-link {{ request()->routeIs('stock.magasins.*') ? 'active' : '' }}">
                  <i class="nav-icon fas fa-warehouse text-success"></i>
                  <p>Magasins Logiques</p>
                </a>
              </li>

              <!-- Mouvements (Treeview) -->
              @php 
                $mvtActive = request()->is('stock/entrees*') || request()->is('stock/sorties*') || request()->is('stock/transferts*');
              @endphp
              <li class="nav-item {{ $mvtActive ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ $mvtActive ? 'active' : '' }}">
                  <i class="nav-icon fas fa-exchange-alt text-primary"></i>
                  <p>
                    Flux & Mouvements
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('stock.entrees.index') }}" class="nav-link {{ request()->routeIs('stock.entrees.*') ? 'active' : '' }}">
                      <i class="nav-icon fas fa-arrow-circle-down text-success"></i>
                      <p>Bons d'Entrée</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('stock.sorties.index') }}" class="nav-link {{ request()->routeIs('stock.sorties.*') ? 'active' : '' }}">
                      <i class="nav-icon fas fa-arrow-circle-right text-danger"></i>
                      <p>Bons de Sortie</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('stock.transferts.index') }}" class="nav-link {{ request()->routeIs('stock.transferts.*') ? 'active' : '' }}">
                      <i class="nav-icon fas fa-random text-warning"></i>
                      <p>Transferts Inter-Mag</p>
                    </a>
                  </li>
                </ul>
              </li>

              <!-- Inventaires -->
              <li class="nav-item">
                <a href="{{ route('stock.inventaires.index') }}" class="nav-link {{ request()->routeIs('stock.inventaires.*') ? 'active' : '' }}">
                  <i class="nav-icon fas fa-clipboard-list text-warning"></i>
                  <p>Inventaires Physiques</p>
                </a>
              </li>

              <!-- Valorisation -->
              <li class="nav-item">
                <a href="{{ route('stock.valorisation.index') }}" class="nav-link {{ request()->routeIs('stock.valorisation.*') ? 'active' : '' }}">
                  <i class="nav-icon fas fa-hand-holding-usd text-success"></i>
                  <p>Valorisation & Rapports</p>
                </a>
              </li>

              <!-- Séparateur -->
              <li class="nav-header text-uppercase text-muted fw-bold small mt-3">Administration</li>

              <!-- Retour Portail -->
              <li class="nav-item">
                <a href="{{ url('/') }}" class="nav-link">
                  <i class="nav-icon fas fa-arrow-left text-secondary"></i>
                  <p>Retour au Portail</p>
                </a>
              </li>

            </ul>
          </nav>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="app-main">
        <div class="app-content pt-3">
          <div class="container-fluid">
            <!-- Flash Messages -->
            @if(session('success'))
              <div class="alert alert-success alert-dismissible fade show small mx-4 mt-2" role="alert">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif
            @if(session('error'))
              <div class="alert alert-danger alert-dismissible fade show small mx-4 mt-2" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @yield('content')
          </div>
        </div>
      </main>

      <!-- Footer -->
      <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">CHU-YO Keystone</div>
        <strong>Copyright &copy; 2026 <a href="#" class="text-decoration-none">CHU-YO</a>.</strong> Tous droits réservés.
      </footer>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('plugins/jquery/jquery-3.7.1.js') }}"></script>
    <script>
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
    </script>
    <script src="{{ asset('plugins/overlayscrollbars/browser/overlayscrollbars.browser.es6.min.js') }}"></script>
    <script src="{{ asset('plugins/popper/umd/popper.min.js') }}"></script>
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('adminlte/js/adminlte.js') }}"></script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>

    <!-- Bootstrap Table -->
    <script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
    <script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>

    <!-- OverlayScrollbars Config -->
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>

    <!-- Ziggy Routes -->
    @routes

    @stack('scripts')
    @stack('js')
  </body>
</html>
