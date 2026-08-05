/**
 * recapitulatif.js — A-03 étape ②, soumission au visa (SW-01).
 *
 * L'écran ne décide de rien : le bouton a déjà été grisé par le serveur si un
 * blocage subsiste, et le serveur REJOUE tous les contrôles à la réception.
 * Ce fichier ne fait que présenter la confirmation chiffrée et relayer.
 */
$(function () {
    const $barre = $('#barre-recapitulatif');
    if ($barre.length === 0) return;

    const $bouton = $('#btn-soumettre');

    $bouton.on('click', function () {
        const numero = $bouton.data('numero');
        const nbLignes = $bouton.data('nb-lignes');
        const montant = $bouton.data('montant-ttc');
        const fournisseur = $bouton.data('fournisseur');

        // SW-01 : confirmation RÉCAPITULATIVE et CHIFFRÉE (SPEC_UX §15.1).
        // On ne demande jamais « êtes-vous sûr ? » sans redire ce qui est en
        // jeu — c'est ce qui distingue une confirmation utile d'un réflexe.
        Swal.fire({
            title: 'Soumettre le bon au visa ?',
            html: `<div class="text-start">
                    <p class="mb-2">${numero} · ${nbLignes} ligne(s) · <strong>${montant} FCFA TTC</strong> · ${fournisseur}</p>
                    <p class="mb-0">Le bon sera <strong>verrouillé</strong> pendant l'examen.
                    Il recevra son numéro définitif à la validation.</p>
                   </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Soumettre au visa',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#198754',
        }).then((resultat) => {
            if (!resultat.isConfirmed) return;
            soumettre();
        });
    });

    const soumettre = () => {
        // Anti-double-soumission : une transition de statut ne doit pas
        // pouvoir partir deux fois (SPEC_UX §0.3).
        if ($bouton.prop('disabled')) return;
        $bouton.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span>Soumission en cours…');

        $.ajax({
            url: $barre.data('url-soumettre'),
            method: 'POST',
            dataType: 'json',
        })
            .done((reponse) => {
                Swal.fire({
                    icon: 'success',
                    title: reponse.message,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                }).then(() => {
                    window.location.href = reponse.data?.redirection ?? $barre.data('url-liste');
                });
            })
            .fail((xhr) => {
                const reponse = xhr.responseJSON ?? {};

                // Blocages détaillés (422) : on les liste, parce qu'un simple
                // « soumission impossible » n'indique pas quoi corriger.
                const detail = Array.isArray(reponse.blocages) && reponse.blocages.length > 0
                    ? `<ul class="text-start mb-0">${
                        reponse.blocages.map((b) => `<li>${$('<span>').text(b.message).html()}</li>`).join('')
                    }</ul>`
                    : null;

                Swal.fire({
                    icon: 'error',
                    title: xhr.status === 409 ? 'Le bon a changé d\'état' : 'Soumission impossible',
                    html: detail,
                    text: detail ? undefined : (reponse.message ?? 'Soumission impossible.'),
                });

                $bouton.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Soumettre au visa');
            });
    };
});
