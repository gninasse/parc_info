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
            {{--
                D-22 — la cloche. Le contenu n'est chargé qu'à l'OUVERTURE :
                une page qui interroge le serveur sans qu'on lui demande rien
                multiplie les requêtes par le nombre d'écrans consultés. Seule
                la pastille est rendue côté serveur, pour qu'elle soit juste
                dès le premier affichage plutôt que de clignoter un zéro.
            --}}
            <li class="nav-item dropdown me-2">
              <a href="#" class="nav-link position-relative" data-bs-toggle="dropdown"
                 id="cloche-achat" role="button" aria-expanded="false"
                 aria-label="Notifications{{ ($notificationsNonLues ?? 0) > 0 ? ' ('.$notificationsNonLues.' non lues)' : '' }}">
                <i class="bi bi-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ ($notificationsNonLues ?? 0) > 0 ? '' : 'd-none' }}"
                      id="pastille-notifications">
                    {{ $notificationsNonLues ?? 0 }}
                    <span class="visually-hidden">notification(s) non lue(s)</span>
                </span>
              </a>
              <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0" style="width: 360px;">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <span class="fw-bold">Notifications</span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                            id="btn-tout-lu">Tout marquer comme lu</button>
                </div>
                <div id="liste-notifications" style="max-height: 320px; overflow-y: auto;">
                    <div class="text-center text-muted small py-4">Chargement…</div>
                </div>
                <div class="border-top px-3 py-2 text-center">
                    <a href="{{ route('achat.preferences-notification') }}" class="small text-decoration-none">
                        <i class="bi bi-sliders me-1"></i>Régler mes notifications
                    </a>
                </div>
              </div>
            </li>

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

@push('js')
<script>
    /*
     * D-22 — la cloche.
     *
     * Le contenu n'est demandé qu'à l'ouverture du menu, et une seule fois
     * par ouverture : interroger le serveur au chargement de chaque page
     * multiplierait les requêtes par le nombre d'écrans consultés, pour une
     * information que l'utilisateur ne regarde presque jamais.
     */
    $(function () {
        const $liste = $('#liste-notifications');
        const $pastille = $('#pastille-notifications');

        function majPastille(nombre) {
            $pastille.text(nombre).toggleClass('d-none', nombre === 0);
        }

        function echapper(texte) {
            // Le titre et le message viennent de données saisies (motif de
            // renvoi, référence d'entrée) : ils sont insérés en HTML, donc
            // ils doivent être échappés ici.
            return $('<div>').text(texte ?? '').html();
        }

        function rendre(notifications) {
            if (notifications.length === 0) {
                $liste.html('<div class="text-center text-muted small py-4">Aucune notification.</div>');
                return;
            }

            $liste.html(notifications.map(function (n) {
                return '<a href="' + (n.url || '#') + '" '
                    + 'class="d-block px-3 py-2 border-bottom text-decoration-none lien-notification '
                    + (n.lue ? '' : 'bg-light') + '" data-id="' + n.id + '">'
                    + '<div class="fw-semibold small text-body">' + echapper(n.titre) + '</div>'
                    + '<div class="small text-muted">' + echapper(n.message) + '</div>'
                    + '<div class="text-muted" style="font-size:.75rem;">' + echapper(n.depuis) + '</div>'
                    + '</a>';
            }).join(''));
        }

        $('#cloche-achat').on('show.bs.dropdown', function () {
            $.getJSON('{{ route('achat.notifications.index') }}')
                .done(function (data) {
                    rendre(data.notifications);
                    majPastille(data.non_lues);
                })
                .fail(function () {
                    $liste.html('<div class="text-center text-danger small py-4">Notifications indisponibles.</div>');
                });
        });

        // Ouvrir une notification la marque lue : c'est le geste naturel, il
        // n'y a rien à cocher en plus. La navigation suit son cours, l'appel
        // part en arrière-plan.
        $liste.on('click', '.lien-notification', function () {
            $.post('{{ url('achat/notifications') }}/' + $(this).data('id') + '/lue');
        });

        $('#btn-tout-lu').on('click', function () {
            $.post('{{ route('achat.notifications.toutes-lues') }}').done(function () {
                majPastille(0);
                $liste.find('.lien-notification').removeClass('bg-light');
            });
        });
    });
</script>
@endpush
