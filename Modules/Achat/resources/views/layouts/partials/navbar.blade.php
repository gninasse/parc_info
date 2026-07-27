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
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center {{ request()->routeIs('achat.dashboard.index') ? 'active fw-bold text-success' : '' }}" href="{{ route('achat.dashboard.index') }}">
          <i class="bi bi-speedometer2 me-1"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center {{ request()->routeIs('achat.articles.*') ? 'active fw-bold text-success' : '' }}" href="{{ route('achat.articles.index') }}">
          <i class="bi bi-card-list me-1"></i> Articles
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center {{ request()->routeIs('achat.bons-commande.*') ? 'active fw-bold text-success' : '' }}" href="{{ route('achat.bons-commande.index') }}">
          <i class="bi bi-file-earmark-text me-1"></i> Bons de Commande
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center {{ request()->routeIs('achat.bordereaux.*') ? 'active fw-bold text-success' : '' }}" href="{{ route('achat.bordereaux.index') }}">
          <i class="bi bi-truck me-1"></i> Livraisons
        </a>
      </li>
    </ul>
    <!--end::Center Navbar Links-->

    <!--begin::End Navbar Links-->
    <ul class="navbar-nav ms-auto">
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
          <li class="user-header text-bg-success">
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
