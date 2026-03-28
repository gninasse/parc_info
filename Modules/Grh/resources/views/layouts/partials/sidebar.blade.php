      <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <!--begin::Brand Link-->
          <a href="{{ url('/') }}" class="brand-link">
            <!--begin::Brand Image-->
            <img
              src="{{ asset('adminlte/assets/img/AdminLTELogo.png') }}"
              alt="AdminLTE Logo"
              class="brand-image opacity-75 shadow"
            />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">CHU-YO</span>
            <!--end::Brand Text-->
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
              <li class="nav-item menu-open">
                <a href="#" class="nav-link active">
                  <i class="nav-icon bi bi-speedometer"></i>
                  <p>
                    KEYSTONE
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  @can('cores.dashboard.view')
                  <li class="nav-item">
                    <a href="{{ route('cores.dashboard') }}" class="nav-link {{ request()->routeIs('cores.dashboard') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-speedometer2"></i>
                      <p>Dashboard</p>
                    </a>
                  </li>
                  @endcan
                  @can('cores.users.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.users.index') }}" class="nav-link {{ request()->routeIs('cores.users.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-people"></i>
                      <p>Utilisateurs</p>
                    </a>
                  </li>
                  @endcan
                  @can('cores.roles.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.roles.index') }}" class="nav-link {{ request()->routeIs('cores.roles.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-shield-lock"></i>
                      <p>Rôles</p>
                    </a>
                  </li>
                  @endcan
                  @can('cores.permissions.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.permissions.index') }}" class="nav-link {{ request()->routeIs('cores.permissions.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-key"></i>
                      <p>Permissions</p>
                    </a>
                  </li>
                  @endcan
                  @can('cores.modules.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.modules.index') }}" class="nav-link {{ request()->routeIs('cores.modules.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-box-seam"></i>
                      <p>Modules</p>
                    </a>
                  </li> 
                  @endcan
                  @can('cores.activities.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.activities.index') }}" class="nav-link {{ request()->routeIs('cores.activities.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-clock-history"></i>
                      <p>Activités</p>
                    </a>
                  </li>
                  @endcan
                </ul>
              </li>

              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-diagram-2"></i>
                  <p>
                    ORGANISATION
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('organisation.sites.index') }}" class="nav-link {{ request()->routeIs('organisation.sites.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-geo-alt"></i>
                      <p>Sites</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('organisation.directions.index') }}" class="nav-link {{ request()->routeIs('organisation.directions.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-building-fill"></i>
                      <p>Directions</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('organisation.services.index') }}" class="nav-link {{ request()->routeIs('organisation.services.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-people-fill"></i>
                      <p>Services</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('organisation.unites.index') }}" class="nav-link {{ request()->routeIs('organisation.unites.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-person-badge"></i>
                      <p>Unités cliniques</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('organisation.batiments.index') }}" class="nav-link {{ request()->routeIs('organisation.batiments.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-buildings"></i>
                      <p>Bâtiments</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('organisation.etages.index') }}" class="nav-link {{ request()->routeIs('organisation.etages.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-layers"></i>
                      <p>Étages</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('organisation.locaux.index') }}" class="nav-link {{ request()->routeIs('organisation.locaux.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-door-closed"></i>
                      <p>Locaux</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('organisation.postes.index') }}" class="nav-link {{ request()->routeIs('organisation.postes.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-pc-display"></i>
                      <p>Postes de travail</p>
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
