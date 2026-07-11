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
            <span class="brand-text fw-semibold">CHU-YO | GRH</span>
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
              <li class="nav-item">
                <a href="{{ route('grh.dashboard') }}" class="nav-link {{ request()->routeIs('grh.dashboard') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-speedometer2"></i>
                  <p>Tableau de bord</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('grh.employes.index') }}" class="nav-link {{ request()->routeIs('grh.employes.*') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-people-fill"></i>
                  <p>Dossiers Employés</p>
                </a>
              </li>

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
