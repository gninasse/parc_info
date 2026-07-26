/**
 * États et statistiques : graphiques, rapports, export PDF.
 */
document.addEventListener('DOMContentLoaded', function () {
    const contexte = window.achatStatistiques;
    if (!contexte) return;

    const formatMontant = (valeur) => new Intl.NumberFormat('fr-FR', {
        style: 'currency', currency: 'XOF', maximumFractionDigits: 0,
    }).format(valeur || 0);

    // ── Graphiques ─────────────────────────────────────────────────────────
    const canvasMensuel = document.getElementById('chart-mensuel');
    if (canvasMensuel) {
        new Chart(canvasMensuel, {
            type: 'line',
            data: {
                labels: contexte.depensesMensuelles.map((m) => m.label),
                datasets: [{
                    label: 'Dépense TTC',
                    data: contexte.depensesMensuelles.map((m) => m.total),
                    backgroundColor: 'rgba(13,110,253,.1)',
                    borderColor: '#0d6efd',
                    borderWidth: 2,
                    tension: .25,
                    fill: true,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => formatMontant(ctx.parsed.y) } },
                },
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    const canvasFournisseurs = document.getElementById('chart-fournisseurs');
    if (canvasFournisseurs && contexte.depensesParFournisseur.length) {
        new Chart(canvasFournisseurs, {
            type: 'doughnut',
            data: {
                labels: contexte.depensesParFournisseur.map((f) => f.label),
                datasets: [{
                    data: contexte.depensesParFournisseur.map((f) => f.total),
                    backgroundColor: ['#198754', '#20c997', '#ffc107', '#dc3545', '#0d6efd', '#6f42c1'],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label} : ${formatMontant(ctx.parsed)}` } },
                },
            },
        });
    }

    const canvasTypes = document.getElementById('chart-types');
    if (canvasTypes && contexte.articlesParType.length) {
        new Chart(canvasTypes, {
            type: 'pie',
            data: {
                labels: contexte.articlesParType.map((a) => a.label),
                datasets: [{
                    data: contexte.articlesParType.map((a) => a.count),
                    backgroundColor: ['#fd7e14', '#0dcaf0', '#6f42c1', '#adb5bd'],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } } },
            },
        });
    }

    // ── Filtres applicables au rapport sélectionné ─────────────────────────
    function ajusterFiltres() {
        const actifs = ($('#report_type option:selected').data('filtres') || '')
            .toString().split(',').filter(Boolean);

        $('.groupe-filtre').each(function () {
            $(this).toggle(actifs.includes($(this).data('filtre')));
        });
    }

    $('#report_type').on('change', ajusterFiltres);
    ajusterFiltres();

    // ── Consultation du rapport ────────────────────────────────────────────
    $('#btn-rechercher').on('click', function () {
        const $bouton = $(this);
        const restaurer = Achat.chargement($bouton, 'Recherche…');

        $.get(contexte.urls.donnees, $('#form-rapport').serialize())
            .done(function (reponse) {
                if (!reponse.success) return;

                const cles = Object.keys(reponse.columns);

                $('#titre-rapport').text(reponse.title);
                $('#compteur-lignes').text(
                    `${reponse.rows.length} ligne${reponse.rows.length > 1 ? 's' : ''}`
                );

                const $entete = $('#entete-rapport').empty();
                cles.forEach((cle) => $entete.append($('<th>').text(reponse.columns[cle])));

                const $corps = $('#corps-rapport').empty();

                if (!reponse.rows.length) {
                    $corps.append(
                        $('<tr>').append(
                            $('<td>').attr('colspan', cles.length)
                                .addClass('text-center text-muted py-4')
                                .text('Aucune donnée ne correspond aux critères sélectionnés.')
                        )
                    );
                } else {
                    reponse.rows.forEach(function (ligne) {
                        const $tr = $('<tr>');
                        // .text() : les valeurs proviennent de saisies utilisateur.
                        cles.forEach((cle) => $tr.append($('<td>').text(ligne[cle] ?? '—')));
                        $corps.append($tr);
                    });
                }

                $('#carte-resultats').removeClass('d-none');
            })
            .fail(function (xhr) {
                Achat.erreur(Achat.messageErreur(xhr, 'Le rapport n\'a pas pu être établi.'));
            })
            .always(restaurer);
    });

    // ── Export PDF ─────────────────────────────────────────────────────────
    $('#btn-exporter').on('click', function () {
        $('#modal-pdf-iframe').attr(
            'src',
            `${contexte.urls.pdf}?${$('#form-rapport').serialize()}`
        );
        new bootstrap.Modal(document.getElementById('modal-pdf')).show();
    });

    // Affiche le rapport par défaut au chargement.
    $('#btn-rechercher').trigger('click');
});
