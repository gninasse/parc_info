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

        @php
          $dashboardActive = request()->routeIs('parc-info.dashboard');

          $ordinateursActive = request()->routeIs('parc-info.ordinateurs.*');
          $uniteCentralesActive = request()->routeIs('parc-info.unite-centrales.*');
          $serveursActive = request()->routeIs('parc-info.serveurs.*') || request()->routeIs('parc-info.serveurs-virtuels.*');
          $mobilesActive = request()->routeIs('parc-info.mobiles.*') && !request()->is('*telephonie*');
          $actifsActive = $ordinateursActive || $uniteCentralesActive || $serveursActive || $mobilesActive;

          $ecransActive = request()->routeIs('parc-info.ecrans.*');
          $imprimantesActive = request()->routeIs('parc-info.imprimantes.*');
          $scannersActive = request()->routeIs('parc-info.scanners.*');
          $camerasActive = request()->routeIs('parc-info.cameras.*');
          $telephonesActive = request()->routeIs('parc-info.telephonie.*');
          $terminauxMobilesActive = request()->routeIs('parc-info.mobiles.*') && request()->is('*telephonie*');
          $terminauxIpActive = request()->routeIs('parc-info.terminaux-ip.*');
          $telephonieActive = $telephonesActive || $terminauxMobilesActive || $terminauxIpActive;

          $logicielsActive = request()->routeIs('parc-info.logiciels.*');
          $licencesActive = request()->routeIs('parc-info.licences.*');
          $consommablesActive = request()->routeIs('parc-info.consommables.*');
          $fournisseursActive = request()->routeIs('parc-info.fournisseurs.*');

          $switchesActive = request()->routeIs('parc-info.switches.*');
          $routeursActive = request()->routeIs('parc-info.routeurs.*');
          $wifiActive = request()->routeIs('parc-info.wifi.*');
          $parefeuxActive = request()->routeIs('parc-info.parefeux.*');
          $reseauActive = $switchesActive || $routeursActive || $wifiActive || $parefeuxActive;

          $onduleursActive = request()->routeIs('parc-info.onduleurs.*');
          $racksActive = request()->routeIs('parc-info.racks.*');
          $brassageActive = request()->routeIs('parc-info.brassage.*');
          $infraActive = $onduleursActive || $racksActive || $brassageActive;

          $etatsActive = request()->routeIs('parc-info.analyse.etats.*');
          $statsActive = request()->routeIs('parc-info.analyse.statistiques.*');

          $refCpusActive = request()->routeIs('parc-info.referentiels.types-cpus.*');
          $refDisquesActive = request()->routeIs('parc-info.referentiels.types-disques.*');
          $refOsActive = request()->routeIs('parc-info.referentiels.types-os.*');
          $refRamsActive = request()->routeIs('parc-info.referentiels.types-rams.*');
          $refMarquesActive = request()->routeIs('parc-info.referentiels.marques.*');
          $refImprimantesActive = request()->routeIs('parc-info.referentiels.types-imprimantes.*');
          $refMobilesActive = request()->routeIs('parc-info.referentiels.types-mobiles.*');
          $refLicencesActive = request()->routeIs('parc-info.referentiels.types-licences.*');
          $refConsommablesActive = request()->routeIs('parc-info.referentiels.types-consommables.*');
          $refEditeursActive = request()->routeIs('parc-info.referentiels.editeurs.*');
          $referentielsActive = request()->routeIs('parc-info.referentiels.*');

          $hardcodedCodes = [
              'ordinateur', 'serveur', 'serveur-virtuel', 'mobile', 'switch', 'routeur',
              'wifi', 'parefeu', 'onduleur', 'rack', 'brassage', 'camera',
              'imprimante', 'scanner', 'telephone', 'terminal-ip', 'ecran', 'unite-centrale'
          ];
          $dynamicCategories = [];
          $dynamicDicts = [];
          $systemDictCodes = [
              'type_cpu', 'type_ram', 'type_disque', 'type_os',
              'type_imprimante', 'type_mobile'
          ];
          try {
              if (\Illuminate\Support\Facades\Schema::hasTable('parc_info_categories_equipements')) {
                  $dynamicCategories = \Modules\ParcInfo\Models\CategorieEquipement::whereNotIn('code', $hardcodedCodes)->get();
              }
              if (\Illuminate\Support\Facades\Schema::hasTable('parc_info_dictionnaires')) {
                  $dynamicDicts = \Modules\ParcInfo\Models\Dictionnaire::whereNotIn('code', $systemDictCodes)->get();
              }
          } catch (\Exception $e) {}
        @endphp

        {{-- Tableau de bord --}}
        @can('parcinfo.dashboard.view')
        <li class="nav-item">
          <a href="{{ route('parc-info.dashboard') }}"
             class="nav-link {{ $dashboardActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Tableau de bord</p>
          </a>
        </li>
        @endcan

        {{-- ── SECTION 1: MATÉRIELS & ACTIFS ── --}}
        @canany(['parcinfo.ordinateurs.index', 'parcinfo.unite-centrales.index', 'parcinfo.serveurs.index', 'parcinfo.mobiles.index'])
        <li class="nav-header text-uppercase small opacity-50">Gestion du Parc</li>

        {{-- Ordinateurs --}}
        @can('parcinfo.ordinateurs.index')
        <li class="nav-item">
          <a href="{{ route('parc-info.ordinateurs.index') }}"
             class="nav-link {{ $ordinateursActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-pc-display"></i>
            <p>Ordinateurs</p>
          </a>
        </li>
        @endcan

        {{-- Unités Centrales --}}
        @can('parcinfo.unite-centrales.index')
        <li class="nav-item">
          <a href="{{ route('parc-info.unite-centrales.index') }}"
             class="nav-link {{ $uniteCentralesActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-pc"></i>
            <p>Unités Centrales</p>
          </a>
        </li>
        @endcan

        {{-- Serveurs & VMs --}}
        @can('parcinfo.serveurs.index')
        <li class="nav-item {{ $serveursActive ? 'menu-open' : '' }}">
          <a href="#" class="nav-link {{ $serveursActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-server"></i>
            <p>
              Serveurs & VMs
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('parc-info.serveurs.index') }}"
                 class="nav-link {{ request()->routeIs('parc-info.serveurs.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Serveurs Physiques</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('parc-info.serveurs-virtuels.index') }}"
                 class="nav-link {{ request()->routeIs('parc-info.serveurs-virtuels.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Machines Virtuelles</p>
              </a>
            </li>
          </ul>
        </li>
        @endcan

        {{-- Mobiles & Tablettes --}}
        @can('parcinfo.mobiles.index')
        <li class="nav-item">
          <a href="{{ route('parc-info.mobiles.index') }}"
             class="nav-link {{ $mobilesActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-phone-vibrate"></i>
            <p>Tablettes & Mobiles</p>
          </a>
        </li>
        @endcan
        @endcanany

        @if(count($dynamicCategories) > 0)
          @php
            $hasAnyDynamicPerm = false;
            foreach($dynamicCategories as $cat) {
                $plural = \Illuminate\Support\Str::plural($cat->code);
                if (auth()->user()->can("parcinfo.{$plural}.index")) {
                    $hasAnyDynamicPerm = true;
                    break;
                }
            }
          @endphp
          @if($hasAnyDynamicPerm)
            <li class="nav-header text-uppercase small opacity-50">Autres Équipements</li>
            @foreach($dynamicCategories as $cat)
              @php
                $plural = \Illuminate\Support\Str::plural($cat->code);
                $isActive = request()->routeIs("parc-info.{$plural}.*");
              @endphp
              @can("parcinfo.{$plural}.index")
                <li class="nav-item">
                  <a href="{{ route("parc-info.{$plural}.index") }}"
                     class="nav-link {{ $isActive ? 'active' : '' }}">
                    <i class="nav-icon bi {{ $cat->icone ?: 'bi-cpu' }}"></i>
                    <p>{{ $cat->libelle }}</p>
                  </a>
                </li>
              @endcan
            @endforeach
          @endif
        @endif

        {{-- ── SECTION 2: PÉRIPHÉRIQUES & COMM ── --}}
        @canany(['parcinfo.imprimantes.index', 'parcinfo.scanners.index', 'parcinfo.telephonie.index', 'parcinfo.terminaux-ip.index', 'parcinfo.cameras.index', 'parcinfo.ecrans.index'])
        <li class="nav-header text-uppercase small opacity-50">Périphériques & Comm</li>

        {{-- Impression & Scanners --}}
        @canany(['parcinfo.imprimantes.index', 'parcinfo.scanners.index'])
        <li class="nav-item {{ $imprimantesActive || $scannersActive ? 'menu-open' : '' }}">
          <a href="#" class="nav-link {{ $imprimantesActive || $scannersActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-printer"></i>
            <p>
              Impression & Scan
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            @can('parcinfo.imprimantes.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.imprimantes.index') }}" class="nav-link {{ $imprimantesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Imprimantes & Copieurs</p>
              </a>
            </li>
            @endcan
            @can('parcinfo.scanners.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.scanners.index') }}" class="nav-link {{ $scannersActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Scanners & Lecteurs</p>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        @endcanany

        {{-- Téléphonie --}}
        @canany(['parcinfo.telephonie.index', 'parcinfo.mobiles.index', 'parcinfo.terminaux-ip.index'])
        <li class="nav-item {{ $telephonieActive ? 'menu-open' : '' }}">
          <a href="#" class="nav-link {{ $telephonieActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-telephone"></i>
            <p>
              Téléphonie & Comm
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            @can('parcinfo.telephonie.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.telephonie.index') }}" class="nav-link {{ $telephonesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Téléphones fixes</p>
              </a>
            </li>
            @endcan
            @can('parcinfo.mobiles.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.mobiles.index') }}" class="nav-link {{ $terminauxMobilesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Terminaux Mobiles</p>
              </a>
            </li>
            @endcan
            @can('parcinfo.terminaux-ip.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.terminaux-ip.index') }}" class="nav-link {{ $terminauxIpActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Terminaux IP</p>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        @endcanany

        {{-- Sécurité / Caméras --}}
        @can('parcinfo.cameras.index')
        <li class="nav-item">
          <a href="{{ route('parc-info.cameras.index') }}" class="nav-link {{ $camerasActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-camera-video"></i>
            <p>Caméras IP</p>
          </a>
        </li>
        @endcan

        {{-- Écrans --}}
        @can('parcinfo.ecrans.index')
        <li class="nav-item">
          <a href="{{ route('parc-info.ecrans.index') }}"
             class="nav-link {{ $ecransActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-display"></i>
            <p>Écrans</p>
          </a>
        </li>
        @endcan
        @endcanany

        {{-- ── SECTION 3: LOGICIELS & STOCKS ── --}}
        @canany(['parcinfo.logiciels.index', 'parcinfo.licences.index', 'parcinfo.consommables.index', 'parcinfo.fournisseurs.index'])
        <li class="nav-header text-uppercase small opacity-50">Logiciels & Stocks</li>

        @can('parcinfo.logiciels.index')
        <li class="nav-item">
          <a href="{{ route('parc-info.logiciels.index') }}"
             class="nav-link {{ $logicielsActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-compact-disc"></i>
            <p>Catalogue Logiciels</p>
          </a>
        </li>
        @endcan

        @can('parcinfo.licences.index')
        <li class="nav-item">
          <a href="{{ route('parc-info.licences.index') }}"
             class="nav-link {{ $licencesActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-file-lock"></i>
            <p>Licences</p>
          </a>
        </li>
        @endcan

        @can('parcinfo.consommables.index')
        <li class="nav-item">
          <a href="{{ route('parc-info.consommables.index') }}"
             class="nav-link {{ $consommablesActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-cart-check"></i>
            <p>Stock Consommables</p>
          </a>
        </li>
        @endcan

        @can('parcinfo.fournisseurs.index')
        <li class="nav-item">
          <a href="{{ route('parc-info.fournisseurs.index') }}"
             class="nav-link {{ $fournisseursActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-truck"></i>
            <p>Fournisseurs</p>
          </a>
        </li>
        @endcan
        @endcanany

        {{-- ── SECTION 4: RÉSEAU & INFRASTRUCTURE ── --}}
        @canany(['parcinfo.switches.index', 'parcinfo.routeurs.index', 'parcinfo.wifi.index', 'parcinfo.parefeux.index', 'parcinfo.onduleurs.index', 'parcinfo.racks.index', 'parcinfo.brassage.index'])
        <li class="nav-header text-uppercase small opacity-50">Réseau & Infrastructure</li>

        {{-- Équipements Réseau --}}
        @canany(['parcinfo.switches.index', 'parcinfo.routeurs.index', 'parcinfo.wifi.index', 'parcinfo.parefeux.index'])
        <li class="nav-item {{ $reseauActive ? 'menu-open' : '' }}">
          <a href="#" class="nav-link {{ $reseauActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-diagram-3"></i>
            <p>
              Équipements Réseau
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            @can('parcinfo.switches.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.switches.index') }}" class="nav-link {{ $switchesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Switches</p>
              </a>
            </li>
            @endcan
            @can('parcinfo.routeurs.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.routeurs.index') }}" class="nav-link {{ $routeursActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Routeurs</p>
              </a>
            </li>
            @endcan
            @can('parcinfo.wifi.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.wifi.index') }}" class="nav-link {{ $wifiActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Points d'accès WiFi</p>
              </a>
            </li>
            @endcan
            @can('parcinfo.parefeux.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.parefeux.index') }}" class="nav-link {{ $parefeuxActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Pare-feux</p>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        @endcanany

        {{-- Infrastructure Physique --}}
        @canany(['parcinfo.onduleurs.index', 'parcinfo.racks.index', 'parcinfo.brassage.index'])
        <li class="nav-item {{ $infraActive ? 'menu-open' : '' }}">
          <a href="#" class="nav-link {{ $infraActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-lightning-charge"></i>
            <p>
              Infrastructure
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            @can('parcinfo.onduleurs.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.onduleurs.index') }}" class="nav-link {{ $onduleursActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Onduleurs</p>
              </a>
            </li>
            @endcan
            @can('parcinfo.racks.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.racks.index') }}" class="nav-link {{ $racksActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Baies & Racks</p>
              </a>
            </li>
            @endcan
            @can('parcinfo.brassage.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.brassage.index') }}" class="nav-link {{ $brassageActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Brassage</p>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        @endcanany
        @endcanany

        {{-- ── SECTION 5: ANALYSE & RAPPORTS ── --}}
        @canany(['parc-info.analyse.etats.view', 'parc-info.analyse.statistiques.view'])
        <li class="nav-header text-uppercase small opacity-50">Analyse & Rapports</li>

        @can('parc-info.analyse.etats.view')
        <li class="nav-item">
          <a href="{{ route('parc-info.analyse.etats.index') }}" class="nav-link {{ $etatsActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-circle-fill text-warning" style="font-size: 0.6rem;"></i>
            <p>États des Équipements</p>
          </a>
        </li>
        @endcan

        @can('parc-info.analyse.statistiques.view')
        <li class="nav-item">
          <a href="{{ route('parc-info.analyse.statistiques.index') }}" class="nav-link {{ $statsActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-graph-up text-success"></i>
            <p>Statistiques</p>
          </a>
        </li>
        @endcan
        @endcanany

        {{-- ── SECTION 6: CONFIGURATIONS & RÉFÉRENTIELS ── --}}
        @canany([
            'parc-info.referentiels.types-cpus.index',
            'parc-info.referentiels.types-disques.index',
            'parc-info.referentiels.types-os.index',
            'parc-info.referentiels.types-rams.index',
            'parc-info.referentiels.marques.index',
            'parc-info.referentiels.types-imprimantes.index',
            'parc-info.referentiels.types-mobiles.index',
            'parc-info.referentiels.types-licences.index',
            'parc-info.referentiels.types-consommables.index',
            'parc-info.referentiels.editeurs.index'
        ])
        <li class="nav-header text-uppercase small opacity-50">Configuration</li>

        <li class="nav-item {{ $referentielsActive ? 'menu-open' : '' }}">
          <a href="#" class="nav-link {{ $referentielsActive ? 'active' : '' }}">
            <i class="nav-icon bi bi-gear"></i>
            <p>
              Tables Référentielles
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            @can('parc-info.referentiels.types-cpus.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.types-cpus.index') }}" class="nav-link {{ $refCpusActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Types CPU</p>
              </a>
            </li>
            @endcan
            @can('parc-info.referentiels.types-disques.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.types-disques.index') }}" class="nav-link {{ $refDisquesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Types Disques</p>
              </a>
            </li>
            @endcan
            @can('parc-info.referentiels.types-os.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.types-os.index') }}" class="nav-link {{ $refOsActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Types OS</p>
              </a>
            </li>
            @endcan
            @can('parc-info.referentiels.types-rams.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.types-rams.index') }}" class="nav-link {{ $refRamsActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Types RAM</p>
              </a>
            </li>
            @endcan
            @can('parc-info.referentiels.marques.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.marques.index') }}" class="nav-link {{ $refMarquesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Marques</p>
              </a>
            </li>
            @endcan
            @can('parc-info.referentiels.types-imprimantes.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.types-imprimantes.index') }}" class="nav-link {{ $refImprimantesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Types Imprimantes</p>
              </a>
            </li>
            @endcan
            @can('parc-info.referentiels.types-mobiles.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.types-mobiles.index') }}" class="nav-link {{ $refMobilesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Types Mobiles</p>
              </a>
            </li>
            @endcan

            @can('parc-info.referentiels.types-licences.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.types-licences.index') }}" class="nav-link {{ $refLicencesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Types Licences</p>
              </a>
            </li>
            @endcan
            @can('parc-info.referentiels.types-consommables.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.types-consommables.index') }}" class="nav-link {{ $refConsommablesActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Types Consommables</p>
              </a>
            </li>
            @endcan
            @can('parc-info.referentiels.editeurs.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.editeurs.index') }}" class="nav-link {{ $refEditeursActive ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Éditeurs</p>
              </a>
            </li>
            @endcan
            @can('parc-info.referentiels.categories.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.categories.index') }}" class="nav-link {{ request()->routeIs('parc-info.referentiels.categories.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Catégories d'équipement</p>
              </a>
            </li>
            @endcan

            @can('parc-info.referentiels.dictionnaires.index')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.dictionnaires.index') }}" class="nav-link {{ request()->routeIs('parc-info.referentiels.dictionnaires.*') && !request()->route('code') ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>Dictionnaires</p>
              </a>
            </li>
            @endcan

            @if(count($dynamicDicts) > 0)
            <li class="nav-header text-uppercase small opacity-50 ps-4 pt-2">Dictionnaires</li>
            @foreach($dynamicDicts as $dict)
            @can('parc-info.referentiels.dictionnaires.manage')
            <li class="nav-item">
              <a href="{{ route('parc-info.referentiels.dictionnaires.valeurs.index', $dict->code) }}" class="nav-link {{ request()->routeIs('parc-info.referentiels.dictionnaires.valeurs.*') && request()->route('code') === $dict->code ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem; opacity: 0.6;"></i>
                <p>{{ $dict->libelle }}</p>
              </a>
            </li>
            @endcan
            @endforeach
            @endif
          </ul>
        </li>
        @endcanany


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
