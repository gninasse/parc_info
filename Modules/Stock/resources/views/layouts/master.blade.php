<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>@yield('title', 'CHU-YO - Stocks & Magasins')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="title" content="CHU-YO - Stocks & Magasins" />
    <meta name="description" content="Module Stocks et Magasins - CHU-YO" />
    <link rel="stylesheet" href="{{ asset('plugins/source-sans-3/index.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/overlayscrollbars/styles/overlayscrollbars.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/bootstrap-icons/font/bootstrap-icons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2-bootstrap-5-theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/tools/tools.css') }}" />

    @stack('css')
  </head>
  <body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="app-wrapper">

      @include('stock::layouts.partials.navbar')

      @include('stock::layouts.partials.sidebar')

      <main class="app-main">
        <div class="app-content-header">
          <div class="container-fluid">
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">@yield('header', 'Tableau de bord')</h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  @yield('breadcrumb', '')
                </ol>
              </div>
            </div>
          </div>
        </div>
        <div class="app-content">
          <div class="container-fluid">
             @yield('content')
          </div>
        </div>
      </main>
      <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">CHU-YO</div>
        <strong>Copyright &copy; 2025&nbsp;<a href="#" class="text-decoration-none">CHU-YO</a>.</strong> tous droits reservés.
      </footer>
    </div>
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
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}" crossorigin="anonymous"></script>
    <script src="{{ asset('adminlte/js/adminlte.js') }}"></script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
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

    @routes

    <!--begin::Socle commun du module Stock (formatters, notifications, erreurs)-->
    <script src="{{ asset('js/modules/stock/commun.js') }}?v={{ time() }}"></script>

    @stack('js')
  </body>
</html>
