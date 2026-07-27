<!--begin::Header-->
<nav class="app-header navbar navbar-expand bg-body">
  <div class="container-fluid">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
          <i class="bi bi-list"></i>
        </a>
      </li>
      <li class="nav-item d-none d-md-block">
        <a href="{{ url('/') }}" class="nav-link"><i class="bi bi-house me-1"></i>Accueil</a>
      </li>
    </ul>

    <ul class="navbar-nav ms-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
          <i class="bi bi-person-circle me-1"></i>{{ auth()->user()?->name }}
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="dropdown-item">
                <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
              </button>
            </form>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>
<!--end::Header-->
