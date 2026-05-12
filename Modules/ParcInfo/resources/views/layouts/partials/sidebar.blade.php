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
            <p>Tableau de bord</p>
          </a>
        </li>

        {{-- ── GESTION DES ACTIFS ── --}}
        <li class="nav-header text-uppercase small opacity-50">Gestion des Actifs</li>

        <li class="nav-item">
          <a href="{{ route('parc-info.ordinateurs.index') }}"
             class="nav-link {{ request()->routeIs('parc-info.ordinateurs.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-pc-display"></i>
            <p>Ordinateurs</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ route('parc-info.serveurs.index') }}"
             class="nav-link {{ request()->routeIs('parc-info.serveurs.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-server"></i>
            <p>Serveurs</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ route('parc-info.mobiles.index') }}"
             class="nav-link {{ request()->routeIs('parc-info.mobiles.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-phone-vibrate"></i>
            <p>Tablettes & Mobiles</p>
          </a>
        </li>

        {{-- ── INFRASTRUCTURE & RÉSEAU ── --}}
        <li class="nav-header text-uppercase small opacity-50">Infrastructure & Réseau</li>

        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs(['parc-info.switches.*', 'parc-info.routeurs.*', 'parc-info.wifi.*', 'parc-info.parefeux.*']) ? 'active' : '' }}">
            <i class="nav-icon bi bi-diagram-3"></i>
            <p>Équipements Réseau <i class="nav-arrow bi bi-chevron-right"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Switches</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Routeurs</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Points d'accès WiFi</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Pare-feux</p>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs(['parc-info.onduleurs.*', 'parc-info.racks.*', 'parc-info.panneaux.*']) ? 'active' : '' }}">
            <i class="nav-icon bi bi-lightning-charge"></i>
            <p>Infrastructure <i class="nav-arrow bi bi-chevron-right"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Onduleurs</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Baies & Racks</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Brassage</p>
              </a>
            </li>
          </ul>
        </li>

        {{-- ── SÉCURITÉ ── --}}
        <li class="nav-header text-uppercase small opacity-50">Sécurité</li>
        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs('parc-info.cameras.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-camera-video"></i>
            <p>Caméras IP</p>
          </a>
        </li>

        {{-- ── PÉRIPHÉRIQUES & IMPRESSION ── --}}
        <li class="nav-header text-uppercase small opacity-50">Périphériques & Impression</li>

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

        {{-- ── TÉLÉPHONIE & COMM. ── --}}
        <li class="nav-header text-uppercase small opacity-50">Téléphonie & Comm.</li>

        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs('parc-info.telephonie.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-telephone"></i>
            <p>Téléphones fixes</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs('parc-info.telephones-mobiles.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-phone"></i>
            <p>Terminaux Mobiles</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="#" class="nav-link {{ request()->routeIs('parc-info.terminaux-ip.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-headset"></i>
            <p>Terminaux IP</p>
          </a>
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
