<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">

  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <a href="{{ route('achat.dashboard.index') }}" class="brand-link">
      <img src="{{ asset('adminlte/assets/img/AdminLTELogo.png') }}" alt="AdminLTE Logo"
           class="brand-image opacity-75 shadow" />
      <span class="brand-text fw-light">CHU-YO | Achats</span>
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

        @php
          $dashboardActive = request()->routeIs('achat.dashboard.index');
          $articlesActive = request()->routeIs('achat.articles.*');
          $bonsCommandeActive = request()->routeIs('achat.bons-commande.*');
          $bordereauxActive = request()->routeIs('achat.bordereaux.*');
          $stocksActive = request()->routeIs('achat.stocks.*');
        @endphp

        {{-- Tableau de bord --}}
        <li class="nav-item">
          <a href="{{ route('achat.dashboard.index') }}"
             class="nav-link {{ $dashboardActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Tableau de bord</p>
          </a>
        </li>

        <li class="nav-header">GESTION DES ACHATS</li>

        {{-- Articles --}}
        <li class="nav-item">
          <a href="{{ route('achat.articles.index') }}"
             class="nav-link {{ $articlesActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-card-list"></i>
            <p>Catalogue Articles</p>
          </a>
        </li>

        {{-- Bons de commande --}}
        <li class="nav-item">
          <a href="{{ route('achat.bons-commande.index') }}"
             class="nav-link {{ $bonsCommandeActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-file-earmark-text"></i>
            <p>Bons de Commande</p>
          </a>
        </li>

        {{-- Bordereaux de livraison --}}
        <li class="nav-item">
          <a href="{{ route('achat.bordereaux.index') }}"
             class="nav-link {{ $bordereauxActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-truck"></i>
            <p>Bordereaux Livraison</p>
          </a>
        </li>

        {{-- Stocks --}}
        <li class="nav-item">
          <a href="{{ route('achat.stocks.index') }}"
             class="nav-link {{ $stocksActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-box-seam"></i>
            <p>Suivi des Stocks</p>
          </a>
        </li>

        <li class="nav-header">NAVIGATION PORTAIL</li>

        {{-- Retour Accueil --}}
        <li class="nav-item">
          <a href="{{ url('/') }}" class="nav-link">
            <i class="nav-icon bi bi-house-door text-info"></i>
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
