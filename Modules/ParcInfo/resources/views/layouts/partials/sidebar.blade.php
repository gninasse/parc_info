      <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <a href="{{ url('/') }}" class="brand-link">
            <img src="{{ asset('adminlte/assets/img/AdminLTELogo.png') }}" alt="AdminLTE Logo" class="brand-image opacity-75 shadow" />
            <span class="brand-text fw-light">CHU-YO</span>
          </a>
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

              {{-- ── Dashboard ── --}}
              <li class="nav-item">
                <a href="{{ route('parc-info.dashboard') }}" class="nav-link {{ request()->routeIs('parc-info.dashboard') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-speedometer2"></i>
                  <p>Dashboard</p>
                </a>
              </li>

              {{-- ── Parc Informatique ── --}}
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-pc-display"></i>
                  <p>
                    Parc Informatique
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-pc-display"></i>
                      <p>Postes de Travail</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-laptop"></i>
                      <p>Ordinateurs Fixes &amp; Portables</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-server"></i>
                      <p>Serveurs &amp; Stockage</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-tablet"></i>
                      <p>Tablettes &amp; Mobiles</p>
                    </a>
                  </li>
                </ul>
              </li>

              {{-- ── Réseau & Infrastructure ── --}}
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-router"></i>
                  <p>
                    Réseau &amp; Infrastructure
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-router"></i>
                      <p>Équipements Réseau</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-shield-lock"></i>
                      <p>Sécurité (Firewalls)</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-building-gear"></i>
                      <p>Cœur de Baie &amp; Infra</p>
                    </a>
                  </li>
                </ul>
              </li>

              {{-- ── Impression & Image ── --}}
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-printer"></i>
                  <p>
                    Impression &amp; Image
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-printer"></i>
                      <p>Imprimantes &amp; Copieurs</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-upc-scan"></i>
                      <p>Scanners &amp; Lecteurs</p>
                    </a>
                  </li>
                </ul>
              </li>

              {{-- ── Communication ── --}}
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-telephone-inbound"></i>
                  <p>
                    Communication
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-telephone-inbound"></i>
                      <p>Terminaux IP</p>
                    </a>
                  </li>
                </ul>
              </li>

            </ul>
            <!--end::Sidebar Menu-->
          </nav>
        </div>
        <!--end::Sidebar Wrapper-->
      </aside>
      <!--end::Sidebar-->
