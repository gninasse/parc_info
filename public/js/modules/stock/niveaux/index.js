/**
 * index.js — État des stocks (UX §2) : filtres (magasin, nature, catégorie en
 * cascade Catalogue, statut d'alerte en segmented control), lignes sous
 * inventaire grisées + cadenas, exports contextualisés, modale MD-SEUIL.
 */
import '../shared/formatters.js';
import { SeuilModal } from './SeuilModal.js';

/** Ligne sous inventaire : grisée + tooltip (le cadenas est dans la colonne Actions). */
window.niveauRowAttributes = function (row) {
    if (!row.sous_inventaire) return {};
    return {
        class: 'ligne-sous-inventaire',
        title: `Article en cours d'inventaire (${row.inventaire_reference})`,
    };
};

/** Actions de ligne : 🎚 seuil (permission + hors inventaire), cadenas sinon. */
window.niveauActionsFormatter = function (value, row) {
    if (row.sous_inventaire) {
        return `<i class="bi bi-lock-fill text-muted" data-bs-toggle="tooltip" title="Article en cours d'inventaire (${row.inventaire_reference})"></i>`;
    }
    if (!window.PEUT_AJUSTER_SEUIL) return '—';
    return `<button class="btn btn-outline-secondary btn-sm btn-seuil" data-id="${row.id}" data-bs-toggle="tooltip" title="Ajuster le seuil">
        <i class="bi bi-sliders"></i>
    </button>`;
};

$(function () {
    const $table = $('#niveaux-table');

    const filtres = () => ({
        magasin_id: $('#filter-magasin').val(),
        nature: $('#filter-nature').val(),
        categorie_id: $('#filter-categorie').val(),
        statut: $('#filter-statut input:checked').val(),
    });

    $table.bootstrapTable('refreshOptions', {
        queryParams: (params) => Object.assign(params, filtres()),
    });

    $('#filter-magasin, #filter-nature, #filter-categorie').on('change', () => $table.bootstrapTable('refresh'));
    $('#filter-statut input').on('change', () => $table.bootstrapTable('refresh'));

    // Catégories Catalogue (cascade 2 niveaux — S11, via l'API inter-modules)
    $.getJSON('/catalogue/api/categories', (res) => {
        const $categorie = $('#filter-categorie');
        (res.data ?? []).forEach((parent) => {
            $categorie.append(new Option(parent.libelle, parent.id));
            (parent.enfants ?? []).forEach((enfant) => {
                $categorie.append(new Option(`— ${enfant.libelle}`, enfant.id));
            });
        });
    });

    // Modale MD-SEUIL
    const seuilModal = new SeuilModal({
        magasins: $('#filter-magasin option').toArray()
            .filter((option) => option.value !== '')
            .map((option) => ({ id: option.value, libelle: option.text })),
        onSaved: () => $table.bootstrapTable('refresh'),
    });

    $table.on('click', '.btn-seuil', function () {
        const id = Number($(this).data('id'));
        const row = $table.bootstrapTable('getData').find((ligne) => ligne.id === id);
        if (row) seuilModal.openForNiveau(row);
    });

    $('#btn-seuil-article').on('click', () => seuilModal.openForArticle());

    // Exports : mêmes filtres que la table (amendement n°18 côté serveur)
    $('.export-lien').on('click', function (e) {
        e.preventDefault();
        const params = new URLSearchParams(
            Object.entries({ ...filtres(), format: $(this).data('format'), search: $('.search-input').val() ?? '' })
                .filter(([, valeur]) => valeur !== undefined && valeur !== null && valeur !== '')
        );
        window.location.href = `${route('stock.niveaux.export')}?${params.toString()}`;
    });

    /*
     * ── D-21 : « Commander » depuis les alertes ──────────────────────────
     *
     * Le magasinier coche les articles qui manquent et ouvre un BROUILLON de
     * commande. C'est la fin du signalement par téléphone, et du délai qu'il
     * imposait : l'acheteur reprend un bon déjà pré-rempli.
     *
     * Trois précautions à l'écran, le serveur restant maître :
     *   - le même article peut apparaître sur plusieurs magasins : on
     *     dédoublonne, sinon la commande porterait deux fois la même ligne ;
     *   - un article sous inventaire est écarté (son niveau est en cours de
     *     recomptage : sa quantité n'est pas fiable à cet instant) ;
     *   - si le serveur ne peut pas déterminer le fournisseur, il le dit, et
     *     on pose la question plutôt que de choisir à sa place.
     */
    const $boutonCommander = $('#btn-commander');

    const articlesSelectionnes = () => {
        const lignes = $table.bootstrapTable('getSelections') ?? [];

        // Dédoublonnage par article : un même article peut manquer dans
        // deux magasins, cela reste UNE ligne de commande.
        const parArticle = new Map();

        lignes
            .filter((ligne) => !ligne.sous_inventaire && ligne.article_id)
            .forEach((ligne) => parArticle.set(ligne.article_id, ligne));

        return Array.from(parArticle.values());
    };

    const rafraichirBoutonCommander = () => {
        if ($boutonCommander.length === 0) return;

        const articles = articlesSelectionnes();
        const $compteur = $('#compteur-commander');

        $boutonCommander.prop('disabled', articles.length === 0);
        $compteur.toggleClass('d-none', articles.length === 0).text(articles.length);
    };

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table load-success.bs.table',
        rafraichirBoutonCommander);

    /** Envoie la demande, en laissant le serveur trancher le fournisseur. */
    const demanderBrouillon = (articles, fournisseurId = null) => {
        const charge = { article_ids: articles.map((a) => a.article_id) };

        if (fournisseurId) charge.fournisseur_id = fournisseurId;

        // Le magasin d'origine : purement informatif, il enrichit le journal.
        const magasin = $('#filter-magasin option:selected').text();
        if ($('#filter-magasin').val()) charge.magasin = magasin;

        $.ajax({
            url: $boutonCommander.data('url'),
            method: 'POST',
            data: JSON.stringify(charge),
            contentType: 'application/json',
            dataType: 'json',
        })
            .done((reponse) => {
                Swal.fire({
                    icon: 'success',
                    title: 'Brouillon créé',
                    text: reponse.message,
                    confirmButtonText: 'Ouvrir le brouillon',
                    showCancelButton: true,
                    cancelButtonText: 'Rester ici',
                }).then((choix) => {
                    if (choix.isConfirmed) window.location.href = reponse.data.url;
                });
            })
            .fail((xhr) => {
                const reponse = xhr.responseJSON ?? {};

                // Le serveur ne peut pas déterminer le fournisseur : on pose
                // la question, on ne choisit pas à sa place.
                if (reponse.motif === 'fournisseur_indetermine') {
                    const choix = (reponse.data?.fournisseurs ?? []);

                    if (choix.length === 0) {
                        Swal.fire({ icon: 'info', title: 'Fournisseur à choisir', text: reponse.message });
                        return;
                    }

                    Swal.fire({
                        icon: 'question',
                        title: 'Quel fournisseur ?',
                        text: reponse.message,
                        input: 'select',
                        inputOptions: Object.fromEntries(choix.map((f) => [f.id, f.nom])),
                        inputPlaceholder: 'Choisir…',
                        showCancelButton: true,
                        confirmButtonText: 'Créer le brouillon',
                        cancelButtonText: 'Annuler',
                    }).then((reponseUtilisateur) => {
                        if (reponseUtilisateur.isConfirmed && reponseUtilisateur.value) {
                            demanderBrouillon(articles, reponseUtilisateur.value);
                        }
                    });

                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Commande impossible',
                    text: reponse.message ?? 'Une erreur est survenue.',
                });
            });
    };

    $boutonCommander.on('click', function () {
        const articles = articlesSelectionnes();

        if (articles.length === 0) return;

        const libelles = articles.slice(0, 5).map((a) => a.article_nom).join(', ');

        Swal.fire({
            icon: 'question',
            title: `Commander ${articles.length} article(s) ?`,
            html: `<p class="mb-1">${libelles}${articles.length > 5 ? '…' : ''}</p>
                   <p class="small text-muted mb-0">Un brouillon de commande sera créé.
                   Les quantités proposées restent modifiables par l'acheteur.</p>`,
            showCancelButton: true,
            confirmButtonText: 'Créer le brouillon',
            cancelButtonText: 'Annuler',
        }).then((choix) => {
            if (choix.isConfirmed) demanderBrouillon(articles);
        });
    });
});
