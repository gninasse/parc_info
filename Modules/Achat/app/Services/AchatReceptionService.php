<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Exceptions\AchatReceptionException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\IntegrationReception;
use Modules\Achat\Models\LigneCommande;

/**
 * Intégration des réceptions physiques venues du Stock — le cœur du
 * raccordement PRQ-05 (`RACCORDEMENT_Achat_Stock.md` §3, API_Inter_Modules §5.1).
 *
 * SERVICE INTERNE TRANSACTIONNEL : appelé DANS la transaction de validation du
 * bon d'entrée, jamais exposé en HTTP. Son échec fait échouer la validation
 * Stock — rien n'est écrit nulle part, ni ici ni là-bas. C'est ce qui garantit
 * que le stock physique et le reste à livrer ne divergent jamais.
 *
 * Trois protections, dans cet ordre :
 *  1. le bon doit être livrable (VALIDE ou PARTIEL) ;
 *  2. le plafond est revérifié SOUS VERROU au moment de l'intégration, pas à
 *     la saisie — deux bons d'entrée concurrents sur le même reste ne peuvent
 *     pas passer tous les deux (IA-4) ;
 *  3. l'idempotence par `entree_id` : un rejeu retourne le résultat initial
 *     sans rien réincrémenter (IA-5).
 */
class AchatReceptionService
{
    /**
     * Intègre une réception physique au bon de commande.
     *
     * @param  int  $bonCommandeId  bon lié au bon d'entrée
     * @param  int  $entreeId  clé naturelle d'idempotence
     * @param  list<array{article_id: int, quantite: float|string}>  $lignesRecues
     * @param  string|null  $reference  n° du bon d'entrée (ENT-…), pour la trace
     *
     * @throws AchatReceptionException
     */
    public function integrer(
        int $bonCommandeId,
        int $entreeId,
        array $lignesRecues,
        ?string $reference = null,
        ?int $parUtilisateur = null,
    ): ResultatIntegration {
        // Idempotence : un bon d'entrée déjà intégré ne l'est pas deux fois.
        $dejaFaite = IntegrationReception::query()
            ->receptions()
            ->where('entree_id', $entreeId)
            ->first();

        if ($dejaFaite !== null) {
            return $this->resultatDepuisTrace($dejaFaite);
        }

        // Verrou sur le bon : sérialise deux intégrations concurrentes.
        $bon = BonCommande::query()->lockForUpdate()->findOrFail($bonCommandeId);

        if (! in_array($bon->statut, BonCommande::STATUTS_RECEPTIONNABLES, true)) {
            throw AchatReceptionException::bonNonLivrable($bon->numero_affiche, $bon->statut_label);
        }

        $lignes = $this->lignesVerrouillees($bon);
        $demandes = $this->regrouperParArticle($lignesRecues);

        $this->verifierArticlesSurLaCommande($demandes, $lignes, $bon);
        $this->verifierPlafonds($demandes, $lignes);

        $detail = $this->appliquerIncrements($demandes, $lignes);

        $this->recalculerStatut($bon);

        IntegrationReception::create([
            'bon_commande_id' => $bon->id,
            'sens' => IntegrationReception::SENS_RECEPTION,
            'entree_id' => $entreeId,
            'reference' => $reference,
            'detail' => $detail,
            'created_by' => $parUtilisateur ?? auth()->id(),
        ]);

        return new ResultatIntegration(
            bonCommandeId: $bon->id,
            numeroBonCommande: $bon->numero_affiche,
            statutBc: $bon->fresh()->statut,
            lignes: $detail,
        );
    }

    /**
     * Contre-passe une réception : décréments symétriques, plancher zéro
     * (API_Inter_Modules §5.2). Un bon LIVRE peut ainsi redevenir PARTIEL.
     *
     * @param  list<array{article_id: int, quantite: float|string}>  $lignesAnnulees
     *
     * @throws AchatReceptionException
     */
    public function contrePasser(
        int $bonCommandeId,
        int $mouvementId,
        array $lignesAnnulees,
        ?string $reference = null,
        ?int $parUtilisateur = null,
    ): ResultatIntegration {
        $dejaFaite = IntegrationReception::query()
            ->contrePassations()
            ->where('mouvement_id', $mouvementId)
            ->first();

        if ($dejaFaite !== null) {
            return $this->resultatDepuisTrace($dejaFaite);
        }

        $bon = BonCommande::query()->lockForUpdate()->findOrFail($bonCommandeId);

        $lignes = $this->lignesVerrouillees($bon);
        $demandes = $this->regrouperParArticle($lignesAnnulees);

        $detail = [];

        foreach ($demandes as $articleId => $quantite) {
            $ligne = $lignes->firstWhere('article_id', $articleId);

            if ($ligne === null) {
                continue; // ligne absente du bon : rien à décrémenter
            }

            // Plancher zéro : une correction ne peut pas rendre le livré négatif.
            $nouvelleLivree = max(0, (float) $ligne->quantite_livree - $quantite);

            $ligne->forceFill(['quantite_livree' => $nouvelleLivree])->save();

            $detail[] = [
                'ligne_id' => $ligne->id,
                'designation' => $ligne->designation,
                'quantite' => -$quantite,
                'nouvelle_livree' => $nouvelleLivree,
                'reste' => round((float) $ligne->quantite - $nouvelleLivree, 2),
            ];
        }

        $this->recalculerStatut($bon);

        IntegrationReception::create([
            'bon_commande_id' => $bon->id,
            'sens' => IntegrationReception::SENS_CONTRE_PASSATION,
            'mouvement_id' => $mouvementId,
            'reference' => $reference,
            'detail' => $detail,
            'created_by' => $parUtilisateur ?? auth()->id(),
        ]);

        return new ResultatIntegration(
            bonCommandeId: $bon->id,
            numeroBonCommande: $bon->numero_affiche,
            statutBc: $bon->fresh()->statut,
            lignes: $detail,
        );
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /** Les lignes du bon, verrouillées : le plafond se vérifie sur du figé. */
    private function lignesVerrouillees(BonCommande $bon): Collection
    {
        return LigneCommande::query()
            ->where('bon_commande_id', $bon->id)
            ->lockForUpdate()
            ->get();
    }

    /**
     * Deux lignes du même article sur un bon d'entrée valent une seule
     * demande : c'est le cumul qui doit respecter le reste, pas chaque ligne
     * prise isolément.
     *
     * @param  list<array{article_id: int, quantite: float|string}>  $lignesRecues
     * @return array<int, float>
     */
    private function regrouperParArticle(array $lignesRecues): array
    {
        $demandes = [];

        foreach ($lignesRecues as $ligne) {
            $articleId = (int) $ligne['article_id'];
            $demandes[$articleId] = round(
                ($demandes[$articleId] ?? 0) + (float) $ligne['quantite'],
                2
            );
        }

        return $demandes;
    }

    /**
     * Pas de mélange commande / hors commande sur un même bon
     * (RACCORDEMENT §2.3) : un article absent du BC est refusé tôt et en clair.
     *
     * @param  array<int, float>  $demandes
     */
    private function verifierArticlesSurLaCommande(array $demandes, Collection $lignes, BonCommande $bon): void
    {
        foreach (array_keys($demandes) as $articleId) {
            if ($lignes->firstWhere('article_id', $articleId) === null) {
                throw AchatReceptionException::ligneHorsCommande(
                    'article #'.$articleId,
                    $bon->numero_affiche
                );
            }
        }
    }

    /**
     * Le plafond, revérifié au moment de l'intégration (IA-4). Toutes les
     * lignes fautives sont collectées AVANT de lever : le magasinier voit
     * l'ensemble du problème, pas la première erreur seulement.
     *
     * @param  array<int, float>  $demandes
     */
    private function verifierPlafonds(array $demandes, Collection $lignes): void
    {
        $depassements = [];

        foreach ($demandes as $articleId => $quantite) {
            $ligne = $lignes->firstWhere('article_id', $articleId);
            $reste = round((float) $ligne->quantite - (float) $ligne->quantite_livree, 2);

            if ($quantite > $reste) {
                $depassements[$ligne->id] = [
                    'designation' => $ligne->designation,
                    'reste' => $reste,
                    'demande' => $quantite,
                ];
            }
        }

        if ($depassements !== []) {
            throw AchatReceptionException::plafondDepasse($depassements);
        }
    }

    /**
     * @param  array<int, float>  $demandes
     * @return list<array{ligne_id: int, designation: string, quantite: float, nouvelle_livree: float, reste: float}>
     */
    private function appliquerIncrements(array $demandes, Collection $lignes): array
    {
        $detail = [];

        foreach ($demandes as $articleId => $quantite) {
            $ligne = $lignes->firstWhere('article_id', $articleId);
            $nouvelleLivree = round((float) $ligne->quantite_livree + $quantite, 2);

            $ligne->forceFill(['quantite_livree' => $nouvelleLivree])->save();

            $detail[] = [
                'ligne_id' => $ligne->id,
                'designation' => $ligne->designation,
                'quantite' => $quantite,
                'nouvelle_livree' => $nouvelleLivree,
                'reste' => round((float) $ligne->quantite - $nouvelleLivree, 2),
            ];
        }

        return $detail;
    }

    /**
     * Statut recalculé depuis les lignes : LIVRE si tout est soldé, PARTIEL
     * dès qu'il reste quelque chose ET qu'au moins une unité est arrivée.
     *
     * Les statuts recalculables sont VALIDE, PARTIEL **et LIVRE** : une
     * contre-passation doit pouvoir faire redescendre un bon soldé en PARTIEL
     * (API_Inter_Modules §5.2). En revanche un bon CLOTURE ou ANNULE ne bouge
     * plus : son sort a été décidé par un acte humain motivé, et une écriture
     * tardive ne doit pas le rouvrir dans le dos du validateur.
     */
    private function recalculerStatut(BonCommande $bon): void
    {
        $recalculables = [
            BonCommande::STATUT_VALIDE,
            BonCommande::STATUT_PARTIEL,
            BonCommande::STATUT_LIVRE,
        ];

        if (! in_array($bon->statut, $recalculables, true)) {
            return;
        }

        $agregats = DB::table('achat_lignes_commande')
            ->where('bon_commande_id', $bon->id)
            ->selectRaw('COALESCE(SUM(quantite), 0) AS commandee')
            ->selectRaw('COALESCE(SUM(quantite_livree), 0) AS livree')
            ->first();

        $commandee = (float) $agregats->commandee;
        $livree = (float) $agregats->livree;

        $statut = match (true) {
            $commandee > 0 && $livree >= $commandee => BonCommande::STATUT_LIVRE,
            $livree > 0 => BonCommande::STATUT_PARTIEL,
            default => BonCommande::STATUT_VALIDE,
        };

        if ($statut !== $bon->statut) {
            $bon->forceFill(['statut' => $statut])->save();
        }
    }

    /** Rejeu : on rend le résultat initial, sans toucher aux quantités. */
    private function resultatDepuisTrace(IntegrationReception $trace): ResultatIntegration
    {
        $bon = $trace->bonCommande;

        return new ResultatIntegration(
            bonCommandeId: $trace->bon_commande_id,
            numeroBonCommande: $bon->numero_affiche,
            statutBc: $bon->statut,
            lignes: $trace->detail ?? [],
            dejaIntegre: true,
        );
    }
}
