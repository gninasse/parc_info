/**
 * Écran Rapports (F8) — quatre rapports filtrables avec export PDF.
 */
(function ($) {
    'use strict';

    const RAPPORTS = {
        entrees: {
            titre: 'Entrées de stock',
            colonnes: ['Numéro', 'Type', 'Origine', 'Article', 'Magasin', 'Quantité', 'Coût unitaire', 'Valeur', 'Date'],
            champs: ['numero', 'type', 'origine', 'article', 'magasin', 'quantite', 'cout_unitaire', 'valeur', 'date'],
            monnaie: ['cout_unitaire', 'valeur'],
        },
        sorties: {
            titre: 'Sorties de stock',
            colonnes: ['Numéro', 'Type', 'Origine', 'Article', 'Magasin', 'Quantité', 'Coût unitaire', 'Valeur', 'Date'],
            champs: ['numero', 'type', 'origine', 'article', 'magasin', 'quantite', 'cout_unitaire', 'valeur', 'date'],
            monnaie: ['cout_unitaire', 'valeur'],
        },
        transferts: {
            titre: 'Transferts inter-magasins',
            colonnes: ['Numéro', 'Article', 'Source', 'Destination', 'Quantité', 'Statut', 'Date'],
            champs: ['numero', 'article', 'source', 'destination', 'quantite', 'statut', 'date'],
            monnaie: [],
        },
        stock: {
            titre: 'Stock par magasin',
            colonnes: ['Magasin', 'Code', 'Article', 'Quantité', 'Seuil', 'Alerte', 'Valeur FIFO'],
            champs: ['magasin', 'code_article', 'article', 'quantite', 'seuil', 'statut_alerte', 'valeur_fifo'],
            monnaie: ['valeur_fifo'],
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        function filtres() {
            return {
                magasin_id: $('#filter-magasin').val(),
                magasin_source_id: $('#filter-magasin').val(),
                statut: $('#filter-statut').val(),
                statut_alerte: $('#filter-alerte').val(),
                date_debut: $('#filter-date-debut').val(),
                date_fin: $('#filter-date-fin').val(),
            };
        }

        function charger() {
            const type = $('#rapport-type').val();
            const config = RAPPORTS[type];

            $('#rapport-titre').text(config.titre);
            $('#groupe-statut').prop('hidden', type !== 'transferts');
            $('#groupe-alerte').prop('hidden', type !== 'stock');
            $('#groupe-date-debut, #groupe-date-fin').prop('hidden', type === 'stock');

            $('#rapport-entetes').html(
                '<tr>' + config.colonnes.map((colonne) => `<th>${colonne}</th>`).join('') + '</tr>'
            );

            const url = new URL(route('stock.rapports.pdf', type), window.location.origin);
            Object.entries(filtres()).forEach(([cle, valeur]) => {
                if (valeur) url.searchParams.set(cle, valeur);
            });
            $('#btn-pdf').attr('href', url.toString());

            $.getJSON(route('stock.rapports.data', type), filtres(), function (reponse) {
                const lignes = (reponse.rows || []).map(function (ligne) {
                    const cellules = config.champs.map(function (champ) {
                        const valeur = ligne[champ];
                        if (config.monnaie.includes(champ)) {
                            return `<td class="text-end">${window.prixFormatter(valeur)}</td>`;
                        }
                        if (champ === 'statut_alerte') {
                            return `<td class="text-center">${window.statutAlerteFormatter(valeur)}</td>`;
                        }
                        return `<td>${valeur ?? '-'}</td>`;
                    });
                    return `<tr>${cellules.join('')}</tr>`;
                });

                $('#rapport-lignes').html(
                    lignes.length
                        ? lignes.join('')
                        : `<tr><td colspan="${config.colonnes.length}" class="text-center text-muted py-4">Aucune donnée.</td></tr>`
                );
            });
        }

        $('#rapport-type, #filter-magasin, #filter-statut, #filter-alerte, #filter-date-debut, #filter-date-fin')
            .on('change', charger);

        charger();

        $('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });
    });
})(jQuery);
