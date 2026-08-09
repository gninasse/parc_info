      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body shadow-sm">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav align-items-center">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
              </a>
            </li>
            <li class="nav-item d-none d-md-block ms-3">
                <a href="{{ route('achat.dashboard') }}" class="btn btn-sm btn-outline-primary fw-bold {{ request()->routeIs('achat.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 me-1"></i> TABLEAU DE BORD
                </a>
            </li>
            @if(Route::has('achat.bons-commande.index'))
            <li class="nav-item d-none d-md-block ms-2">
                {{-- Badge rouge « à valider » : visible seulement pour qui détient
                     le visa, et seulement s'il y a effectivement à viser (SPEC_UX §0.1) --}}
                <a href="{{ route('achat.bons-commande.index') }}" class="btn btn-sm btn-outline-primary fw-bold position-relative {{ request()->routeIs('achat.bons-commande.*') ? 'active' : '' }}">
                    <i class="bi bi-card-checklist me-1"></i> BONS DE COMMANDE
                    {{-- Comptage fourni par le view composer du module : une
                         seule requête par page, et zéro si l'utilisateur n'a
                         pas le visa. --}}
                    @if(($bonsAValider ?? 0) > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $bonsAValider }}
                            <span class="visually-hidden">bon(s) à valider</span>
                        </span>
                    @endif
                </a>
            </li>
            @endif
          </ul>
          <!--end::Start Navbar Links-->

          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto align-items-center">
            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                <img
                  src="{{ Auth::user()->avatar_url }}"
                  class="user-image rounded-circle shadow-sm"
                  alt="User Image"
                  style="width: 32px; height: 32px; object-fit: cover;"
                />
                <span class="d-none d-md-inline ms-2 fw-semibold">{{ Auth::user()->name }}</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow-lg border-0">
                <!--begin::User Image-->
                <li class="user-header bg-primary text-white rounded-top py-4">
                  <img
                    src="{{ Auth::user()->avatar_url }}"
                    class="rounded-circle shadow-sm mb-2"
                    alt="User Image"
                    style="width: 80px; height: 80px; object-fit: cover; border: 3px solid rgba(255,255,255,0.2);"
                  />
                  <p class="mb-0 fw-bold">
                    {{ Auth::user()->name }}
                  </p>
                  <small class="opacity-75">Connecté</small>
                </li>
                <!--end::User Image-->

                <!--begin::Menu Footer-->
                <li class="user-footer p-3 bg-light d-flex justify-content-between">
                  <a href="{{ route('cores.profile') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-person me-1"></i> Mon Profil
                  </a>
                  {{--
                      Déconnexion : c'est une ACTION, pas une navigation. Un <a> qui
                      soumet un formulaire déroute les technologies d'assistance (le
                      lecteur d'écran annonce un lien vers nulle part) et ne répond pas
                      à la barre d'espace. Un vrai bouton dans le formulaire règle les
                      deux, sans JavaScript.
                  --}}
                  <form action="{{ route('logout') }}" method="POST" class="m-0">
                      @csrf
                      <button type="submit" class="btn btn-danger btn-sm">
                          <i class="bi bi-box-arrow-right me-1"></i> Quitter
                      </button>
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
