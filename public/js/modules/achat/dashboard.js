/**
 * Tableau de bord du module Achat.
 * Les données sont injectées par la vue (window.achatDashboard) : aucun appel réseau.
 */
document.addEventListener('DOMContentLoaded', function () {
    const donnees = window.achatDashboard;
    if (!donnees) return;

    const formatMontant = (valeur) => new Intl.NumberFormat('fr-FR', {
        style: 'currency', currency: 'XOF', maximumFractionDigits: 0,
    }).format(valeur);

    // ── Dépense mensuelle ──────────────────────────────────────────────────
    const canvasMensuel = document.getElementById('chart-depenses-mensuelles');

    if (canvasMensuel) {
        new Chart(canvasMensuel, {
            type: 'line',
            data: {
                labels: donnees.depensesMensuelles.map((m) => m.label),
                datasets: [{
                    label: 'Dépense engagée TTC',
                    data: donnees.depensesMensuelles.map((m) => m.total),
                    backgroundColor: 'rgba(13, 110, 253, .1)',
                    borderColor: '#0d6efd',
                    borderWidth: 2,
                    tension: .25,
                    fill: true,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => formatMontant(ctx.parsed.y) } },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (v) => formatMontant(v) } },
                },
            },
        });
    }

    // ── Répartition par fournisseur ────────────────────────────────────────
    const canvasFournisseurs = document.getElementById('chart-fournisseurs');

    if (canvasFournisseurs) {
        const repartition = donnees.depensesParFournisseur;

        if (!repartition.length) {
            canvasFournisseurs.closest('.card-body').innerHTML =
                '<div class="text-center text-muted py-5">' +
                '<i class="fas fa-chart-pie fa-2x mb-3 d-block opacity-50"></i>' +
                'Aucune dépense engagée à ce jour.</div>';
            return;
        }

        new Chart(canvasFournisseurs, {
            type: 'doughnut',
            data: {
                labels: repartition.map((f) => f.label),
                datasets: [{
                    data: repartition.map((f) => f.total),
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#0dcaf0'],
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label} : ${formatMontant(ctx.parsed)}` } },
                },
            },
        });
    }
});
