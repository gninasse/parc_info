/**
 * index.js — A-01, tableau de bord du module Achat (D-18).
 *
 * Le graphique Z2 lit les données RENDUES par le serveur (attribut
 * data-evolution), calculées par le service partagé. Le navigateur ne
 * recalcule ni n'agrège rien : il dessine — sans quoi le graphique et la
 * carte de rapport finiraient par diverger.
 *
 * La bascule HT/TTC ne recharge pas la page : les deux séries sont déjà là.
 */

$(function () {
    const canvas = document.getElementById('graphique-evolution');
    if (!canvas || typeof Chart === 'undefined') return;

    const evolution = JSON.parse(canvas.dataset.evolution || '[]');
    if (evolution.length === 0) return;

    const libelles = evolution.map((mois) => mois.libelle);

    const fcfa = (valeur) => `${Number(valeur).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} FCFA`;

    const graphique = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: libelles,
            datasets: [{
                label: 'Dépenses engagées (HT)',
                data: evolution.map((mois) => mois.montant_ht),
                backgroundColor: 'rgba(13, 110, 253, .65)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        // L'infobulle porte le NOMBRE DE BONS en plus du
                        // montant : un pic à 12 M sur un seul bon ne se lit
                        // pas comme douze bons à 1 M.
                        label: (contexte) => {
                            const mois = evolution[contexte.dataIndex];
                            const nombre = mois.nombre;
                            return [
                                fcfa(contexte.parsed.y),
                                `${nombre} bon${nombre > 1 ? 's' : ''} engagé${nombre > 1 ? 's' : ''}`,
                            ];
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (valeur) => Number(valeur).toLocaleString('fr-FR', { notation: 'compact' }),
                    },
                },
            },
        },
    });

    // ── Bascule HT / TTC ──────────────────────────────────────────────────

    $('input[name="base-montant"]').on('change', function () {
        const ttc = this.value === 'ttc';

        graphique.data.datasets[0].data = evolution.map((mois) => (ttc ? mois.montant_ttc : mois.montant_ht));
        graphique.data.datasets[0].label = `Dépenses engagées (${ttc ? 'TTC' : 'HT'})`;
        graphique.update();
    });

    // Clic sur une barre : le rapport, filtré sur le mois cliqué (UX-03 —
    // chaque chiffre est justifiable par la liste qui le compose).
    canvas.addEventListener('click', (evenement) => {
        const points = graphique.getElementsAtEventForMode(evenement, 'nearest', { intersect: true }, true);
        if (points.length === 0) return;

        const mois = evolution[points[0].index];
        if (!mois || typeof route !== 'function') return;

        const debut = `${mois.mois}-01`;
        const fin = new Date(new Date(debut).getFullYear(), new Date(debut).getMonth() + 1, 0)
            .toISOString()
            .slice(0, 10);

        window.location.href = `${route('achat.rapports.index')}?du=${debut}&au=${fin}`;
    });
});
