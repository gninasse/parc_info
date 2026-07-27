<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">

  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <a href="{{ url('/') }}" class="brand-link d-flex align-items-center gap-2 px-3 py-2">
      <img src="{{ asset('images/chuyo_icon.png') }}" alt="CHU-YO"
           class="brand-image" style="width: 36px; height: 36px; object-fit: contain; border-radius: 8px;" />
      <span class="brand-text fw-semibold">CHU-YO | Achats</span>
    </a>
  </div>
  <!--end::Sidebar Brand-->

  <!--begin::Sidebar Wrapper-->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column"
          data-lte-toggle="treeview"
          role="navigation"
          aria-label="Navigation principale"
          data-accordion="false"
          id="navigation">

        {{-- ENF-SEC-03 : une entrée de menu non autorisée n'est pas rendue. --}}

        @can('achat.dashboard.view')
        <li class="nav-item">
          <a href="{{ route('achat.dashboard.index') }}"
             class="nav-link {{ request()->routeIs('achat.dashboard.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Tableau de bord</p>
          </a>
        </li>
        @endcan

        <li class="nav-header">GESTION DES ACHATS</li>

        @can('achat.articles.view')
        <li class="nav-item">
          <a href="{{ route('achat.articles.index') }}"
             class="nav-link {{ request()->routeIs('achat.articles.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-card-list"></i>
            <p>Catalogue Articles</p>
          </a>
        </li>
        @endcan

        @can('achat.bons_commande.view')
        <li class="nav-item">
          <a href="{{ route('achat.bons-commande.index') }}"
             class="nav-link {{ request()->routeIs('achat.bons-commande.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-file-earmark-text"></i>
            <p>Bons de Commande</p>
          </a>
        </li>
        @endcan

        @can('achat.bordereaux.view')
        <li class="nav-item">
          <a href="{{ route('achat.bordereaux.index') }}"
             class="nav-link {{ request()->routeIs('achat.bordereaux.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-truck"></i>
            <p>Bordereaux Livraison</p>
          </a>
        </li>
        @endcan

        @can('achat.stocks.view')
        <li class="nav-item">
          <a href="{{ route('achat.stocks.index') }}"
             class="nav-link {{ request()->routeIs('achat.stocks.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-box-seam"></i>
            <p>Suivi des Stocks</p>
          </a>
        </li>
        @endcan

        @can('achat.rapports.view')
        <li class="nav-header">ANALYSE & RAPPORTS</li>
        <li class="nav-item">
          <a href="{{ route('achat.statistiques.index') }}"
             class="nav-link {{ request()->routeIs('achat.statistiques.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-graph-up-arrow"></i>
            <p>États & Statistiques</p>
          </a>
        </li>
        @endcan

        @can('achat.parametres.view')
        <li class="nav-header">ADMINISTRATION</li>
        <li class="nav-item">
          <a href="{{ route('achat.parametres.index') }}"
             class="nav-link {{ request()->routeIs('achat.parametres.*') ? 'active' : '' }}">
            <i class="nav-icon bi bi-sliders"></i>
            <p>Paramètres</p>
          </a>
        </li>
        @endcan

        @include('core::partials.sidebar-modules', ['moduleCourant' => 'achat'])

        <li class="nav-header">NAVIGATION PORTAIL</li>
        <li class="nav-item">
          <a href="{{ url('/') }}" class="nav-link">
            <i class="nav-icon bi bi-house-door text-info"></i>
            <p>Accueil général</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->

</aside>
<!--end::Sidebar-->
