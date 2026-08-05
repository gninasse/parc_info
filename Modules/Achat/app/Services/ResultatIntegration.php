<?php

namespace Modules\Achat\Services;

/**
 * Résultat d'une intégration de réception (API_Inter_Modules §5.1).
 *
 * Renvoyé à l'écran Stock pour son compte-rendu de validation : le magasinier
 * doit voir l'effet de SON geste sur la commande (« 6/10 intégrées, reste 4 »),
 * sans avoir à ouvrir le module Achat.
 */
final class ResultatIntegration
{
    /**
     * @param  list<array{ligne_id: int, designation: string, quantite: float, nouvelle_livree: float, reste: float}>  $lignes
     */
    public function __construct(
        public readonly int $bonCommandeId,
        public readonly string $numeroBonCommande,
        public readonly string $statutBc,
        public readonly array $lignes,
        public readonly bool $dejaIntegre = false,
    ) {}

    /** Phrase de chronologie, telle qu'elle apparaîtra dans le journal. */
    public function resume(string $referenceEntree): string
    {
        $unites = array_sum(array_column($this->lignes, 'quantite'));
        $quantite = rtrim(rtrim(number_format($unites, 2, ',', ' '), '0'), ',');

        return sprintf(
            'Réception %s (%s unité%s) intégrée au bon %s',
            $referenceEntree,
            $quantite,
            $unites > 1 ? 's' : '',
            $this->numeroBonCommande
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'bon_commande_id' => $this->bonCommandeId,
            'numero' => $this->numeroBonCommande,
            'statut_bc' => $this->statutBc,
            'deja_integre' => $this->dejaIntegre,
            'lignes' => $this->lignes,
        ];
    }
}
