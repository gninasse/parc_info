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

              <!-- SECTION A : PARC INFORMATIQUE -->
            <li class="nav-section-title mt-4 mb-1 text-uppercase text-muted fw-bold small ps-3 tracking-wider">
                Parc Informatique
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-pc-display me-3 text-secondary"></i>
                        Postes de Travail
                    </div>
                    <span class="badge bg-secondary rounded-pill small">42</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-laptop me-3 text-secondary"></i>
                    Ordinateurs Portables
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-server me-3 text-secondary"></i>
                    Serveurs & Stockage
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-tablet me-3 text-secondary"></i>
                    Tablettes & Mobiles
                </a>
            </li>

            <!-- SECTION B : RÉSEAU & INFRASTRUCTURE -->
            <li class="nav-section-title mt-4 mb-1 text-uppercase text-muted fw-bold small ps-3 tracking-wider">
                Réseau & Infrastructure
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-router me-3 text-secondary"></i>
                    Équipements Réseau
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-shield-lock me-3 text-secondary"></i>
                    Sécurité (Firewalls)
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-building-gear me-3 text-secondary"></i>
                    Cœur de Baie & Infra
                </a>
            </li>

            <!-- SECTION C : IMPRESSION & NUMÉRISATION -->
            <li class="nav-section-title mt-4 mb-1 text-uppercase text-muted fw-bold small ps-3 tracking-wider">
                Impression & Image
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-printer me-3 text-secondary"></i>
                    Imprimantes & Copieurs
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-upc-scan me-3 text-secondary"></i>
                    Scanners & Lecteurs
                </a>
            </li>

            <!-- SECTION D : TÉLÉPHONIE -->
            <li class="nav-section-title mt-4 mb-1 text-uppercase text-muted fw-bold small ps-3 tracking-wider">
                Communication
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-telephone-inbound me-3 text-secondary"></i>
                    Terminaux IP
                </a>
            </li><!-- SECTION A : PARC INFORMATIQUE -->
            <li class="nav-section-title mt-4 mb-1 text-uppercase text-muted fw-bold small ps-3 tracking-wider">
                Parc Informatique
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-pc-display me-3 text-secondary"></i>
                        Postes de Travail
                    </div>
                    <span class="badge bg-secondary rounded-pill small">42</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-laptop me-3 text-secondary"></i>
                    Ordinateurs Portables
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-server me-3 text-secondary"></i>
                    Serveurs & Stockage
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-tablet me-3 text-secondary"></i>
                    Tablettes & Mobiles
                </a>
            </li>

            <!-- SECTION B : RÉSEAU & INFRASTRUCTURE -->
            <li class="nav-section-title mt-4 mb-1 text-uppercase text-muted fw-bold small ps-3 tracking-wider">
                Réseau & Infrastructure
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-router me-3 text-secondary"></i>
                    Équipements Réseau
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-shield-lock me-3 text-secondary"></i>
                    Sécurité (Firewalls)
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-building-gear me-3 text-secondary"></i>
                    Cœur de Baie & Infra
                </a>
            </li>

            <!-- SECTION C : IMPRESSION & NUMÉRISATION -->
            <li class="nav-section-title mt-4 mb-1 text-uppercase text-muted fw-bold small ps-3 tracking-wider">
                Impression & Image
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-printer me-3 text-secondary"></i>
                    Imprimantes & Copieurs
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-upc-scan me-3 text-secondary"></i>
                    Scanners & Lecteurs
                </a>
            </li>

            <!-- SECTION D : TÉLÉPHONIE -->
            <li class="nav-section-title mt-4 mb-1 text-uppercase text-muted fw-bold small ps-3 tracking-wider">
                Communication
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link link-dark d-flex align-items-center">
                    <i class="bi bi-telephone-inbound me-3 text-secondary"></i>
                    Terminaux IP
                </a>
            </li>

              {{-- ── Root Equipements ── --}}
              <li class="nav-item menu-open">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-pc-display-horizontal"></i>
                  <p>
                    Equipements
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  
                  {{-- ── Informatique ── --}}
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-cpu"></i>
                      <p>Informatique <i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-pc-display"></i>
                          <p>Ordinateurs fixes</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-laptop"></i>
                          <p>Ordinateurs portables</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-server"></i>
                          <p>Serveurs</p>
                        </a>
                      </li>
                    </ul>
                  </li>

                  {{-- ── Réseau ── --}}
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-router"></i>
                      <p>Réseau <i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-diagram-3"></i>
                          <p>Switches</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-router"></i>
                          <p>Routeurs</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-wifi"></i>
                          <p>Points d'accès WiFi</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-shield-lock"></i>
                          <p>Pare-feux</p>
                        </a>
                      </li>
                    </ul>
                  </li>

                  {{-- ── Périphériques ── --}}
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-mouse"></i>
                      <p>Périphériques <i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-display"></i>
                          <p>Moniteurs</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-printer"></i>
                          <p>Imprimantes</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-upc-scan"></i>
                          <p>Scanners</p>
                        </a>
                      </li> 
                    </ul>
                  </li>

                  {{-- ── Téléphonie ── --}}
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-telephone"></i>
                      <p>Téléphonie <i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-telephone-fill"></i>
                          <p>Téléphones fixes</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-phone"></i>
                          <p>Téléphones mobiles</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-tablet"></i>
                          <p>Tablettes</p>
                        </a>
                      </li>
                    </ul>
                  </li>

                  {{-- ── Infrastructure ── --}}
                  <li class="nav-item">
                    <a href="#" class="nav-link">
                      <i class="nav-icon bi bi-building-gear"></i>
                      <p>Infrastructure <i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-battery-charging"></i>
                          <p>Onduleurs</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-grid-3x2-gap"></i>
                          <p>Racks</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="#" class="nav-link">
                          <i class="nav-icon bi bi-plug"></i>
                          <p>Panneaux de brassage</p>
                        </a>
                      </li>
                    </ul>
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
