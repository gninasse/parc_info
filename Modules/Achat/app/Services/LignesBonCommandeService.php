<?php

namespace Modules\Achat\Services;

use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Catalogue\Models\Article;

/**
 * Écriture des lignes d'un brouillon — le gardien de IA-2 (valeurs figées).
 *
 * À l'AJOUT d'un article, la désignation, la nature et le taux de TVA sont
 * COPIÉS du Catalogue et n'en dépendent plus jamais : renommer l'article ou
 * changer son taux de TVA au Catalogue ne réécrit pas un bon existant. C'est
 * la photographie contractuelle du SFD §6.2 — le document dit ce qui a été
 * engagé, et une évolution du référentiel ne réécrit pas le passé.
 *
 * Le prix, lui, est PRÉ-REMPLI depuis `prix_indicatif` mais reste modifiable :
 * c'est un prix négocié, pas un prix catalogue.
 *
 * Corollaire tout aussi important : une ligne DÉJÀ posée n'est jamais
 * re-synchronisée sur le Catalogue. On ne met à jour que ce que l'utilisateur
 * a effectivement saisi (quantité, prix, taux), jamais la photographie.
 */
class LignesBonCommandeService
{
    public function __construct(private readonly CalculMontantsService $montants) {}

    /**
     * Aligne les lignes du bon sur la charge reçue, puis recalcule les
     * montants. À appeler DANS la transaction de l'appelant.
     *
     * @param  list<array<string, mixed>>  $lignesSoumises
     */
    public function synchroniser(BonCommande $bon, array $lignesSoumises): void
    {
        $existantes = $bon->lignes()->get()->keyBy('id');
        $conservees = [];

        foreach ($lignesSoumises as $donnees) {
            $ligne = isset($donnees['id']) ? $existantes->get((int) $donnees['id']) : null;

            $ligne = $ligne === null
                ? $this->creer($bon, $donnees)
                : $this->mettreAJour($ligne, $donnees);

            $conservees[] = $ligne->id;
        }

        // Les lignes absentes de la charge ont été retirées à l'écran.
        $bon->lignes()->whereNotIn('id', $conservees ?: [0])->delete();

        $this->montants->recalculer($bon);
    }

    /**
     * Création : c'est le SEUL moment où l'on lit le Catalogue. Tout ce qui
     * est copié ici est figé pour la vie du bon.
     */
    private function creer(BonCommande $bon, array $donnees): LigneCommande
    {
        $article = Article::query()->findOrFail((int) $donnees['article_id']);

        return $bon->lignes()->create([
            'article_id' => $article->id,
            // ── Photographie contractuelle (IA-2) ──────────────────────────
            'designation' => $article->nom,
            'nature' => $article->nature,
            // Le taux du Catalogue, ou 18 % à défaut : une ligne sans taux
            // rendrait le calcul de TVA impossible (contrat API §2.1).
            'taux_tva' => $donnees['taux_tva'] ?? $article->taux_tva ?? 18.00,
            // D-24 : l'imputation comptable est figée elle aussi. Réaffecter
            // un article à un autre compte au Catalogue ne doit pas réécrire
            // l'imputation d'exercices déjà clos.
            'compte_comptable' => $article->compte_comptable,
            // ── Valeurs négociées, saisies par l'acheteur ─────────────────
            'quantite' => $donnees['quantite'],
            'prix_unitaire_ht' => $donnees['prix_unitaire_ht'] ?? $article->prix_indicatif ?? 0,
        ]);
    }

    /**
     * Mise à jour : quantité, prix et taux seulement.
     *
     * `designation` et `nature` ne figurent volontairement PAS ici. Les
     * réécrire depuis le Catalogue à chaque enregistrement viderait la
     * photographie de son sens : il suffirait de modifier l'article pour
     * changer rétroactivement ce qui a été commandé.
     */
    private function mettreAJour(LigneCommande $ligne, array $donnees): LigneCommande
    {
        $ligne->update([
            'quantite' => $donnees['quantite'],
            'prix_unitaire_ht' => $donnees['prix_unitaire_ht'],
            'taux_tva' => $donnees['taux_tva'] ?? $ligne->taux_tva,
        ]);

        return $ligne;
    }
}
