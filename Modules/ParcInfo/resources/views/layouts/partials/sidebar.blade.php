<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">

  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <a href="{{ url('/') }}" class="brand-link">
      <img src="{{ asset('adminlte/assets/img/AdminLTELogo.png') }}" alt="AdminLTE Logo"
           class="brand-image opacity-75 shadow" />
      <span class="brand-text fw-light">CHU-YO | Parc Info</span>
    </a>
  </div>
  <!--end::Sidebar Brand-->

  <!--begin::Sidebar Wrapper-->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <!--begin::Sidebar Menu-->
      <ul class="nav sidebar-menu flex-column"
          data-lte-toggle="treeview"
          role="navigation"
          aria-label="Main navigation"
          data-accordion="false"
          id="navigation">

        {{-- Dashboard --}}
        <li class="nav-item">
          <a href="{{ route('parc-info.dashboard') }}"
             class="nav-link {{ request()->routeIs('parc-info.dashboard') ? 'active' : '' }}">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Dashboard</p>
          </a>
        </li>

        {{-- ── PARC INFORMATIQUE ── --}}
        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs('parc-info.informatique.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-cpu"></i>
            <p>Parc Informatique <i class="nav-arrow bi bi-chevron-right"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('parc-info.ordinateurs-fixes.index') }}" class="nav-link {{ request()->routeIs('parc-info.ordinateurs-fixes.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-pc-display"></i>
                <p>Ordinateurs fixes</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.ordinateurs-portables.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-laptop"></i>
                <p>Ordinateurs portables</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.serveurs.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-server"></i>
                <p>Serveurs</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.tablettes.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-tablet"></i>
                <p>Tablettes & Mobiles</p>
              </a>
            </li>
          </ul>
        </li>

        {{-- ── RÉSEAU & INFRASTRUCTURE ── --}}
        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs('parc-info.reseau.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-router"></i>
            <p>Réseau & Infrastructure <i class="nav-arrow bi bi-chevron-right"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.switches.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-diagram-3"></i>
                <p>Switches</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.routeurs.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-router"></i>
                <p>Routeurs</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.wifi.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-wifi"></i>
                <p>Points d'accès WiFi</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.parefeux.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-shield-lock"></i>
                <p>Pare-feux</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.onduleurs.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-battery-charging"></i>
                <p>Onduleurs</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.racks.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-grid-3x2-gap"></i>
                <p>Racks</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.panneaux.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-plug"></i>
                <p>Panneaux de brassage</p>
              </a>
            </li>
          </ul>
        </li>

        {{-- ── IMPRESSION & IMAGE ── --}}
        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs('parc-info.impression.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-printer"></i>
            <p>Impression & Image <i class="nav-arrow bi bi-chevron-right"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.imprimantes.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-printer"></i>
                <p>Imprimantes & Copieurs</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.scanners.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-upc-scan"></i>
                <p>Scanners & Lecteurs</p>
              </a>
            </li>
          </ul>
        </li>

        {{-- ── COMMUNICATION ── --}}
        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs('parc-info.communication.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-telephone"></i>
            <p>Communication <i class="nav-arrow bi bi-chevron-right"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.telephones-fixes.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-telephone-fill"></i>
                <p>Téléphones fixes</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.telephones-mobiles.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-phone"></i>
                <p>Téléphones mobiles</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link {{ request()->routeIs('parc-info.terminaux-ip.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-telephone-inbound"></i>
                <p>Terminaux IP</p>
              </a>
            </li>
          </ul>
        </li>

        {{-- ── Retour accueil ── --}}
        <li class="nav-item mt-4 border-top border-secondary pt-3">
          <a href="{{ url('/') }}" class="nav-link text-warning">
            <i class="nav-icon bi bi-house-door"></i>
            <p>Accueil général</p>
          </a>
        </li>

      </ul>
      <!--end::Sidebar Menu-->
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->

</aside>
<!--end::Sidebar-->
