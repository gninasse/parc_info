<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\ParcInfo\Models\Equipement;

/**
 * Sérialisation D10 (S10) : création des fiches parc_info_equipements à la
 * validation d'une entrée, via le modèle Eloquent ParcInfo existant — jamais
 * de duplication de sa logique métier. Le créateur est porté par l'activity
 * log (causer), la table équipements n'ayant pas de created_by.
 */
class SerialisationService
{
    /**
     * Fiche héritée de l'article Catalogue : catégorie, marque, modèle.
     * Statut « en stock ». État : celui saisi au wizard rangée par rangée
     * (référentiel ParcInfo bon/passable/mauvais/avarie), « bon » par défaut.
     */
    public function creerFiche(Article $article, string $numeroSerie, ?float $valeurAchat = null, ?string $etat = null): Equipement
    {
        return Equipement::create([
            'categorie_id' => $article->categorie_equipement_id,
            'code_inventaire' => $this->prochainCode(),
            'numero_serie' => $numeroSerie,
            'marque_id' => $article->marque_id,
            'modele' => $article->modele ?: $article->nom,
            'date_acquisition' => now()->toDateString(),
            'valeur_achat' => $valeurAchat,
            'statut' => 'en_stock',
            'etat' => $etat ?: 'bon',
        ]);
    }

    /**
     * Code EQP-{année}-{séq 4 chiffres} (chips UX « EQP-2024-0117 »), sous
     * verrou par année — pattern stock_sequences (S4).
     */
    private function prochainCode(): string
    {
        $annee = now()->year;

        return DB::transaction(function () use ($annee) {
            DB::table('stock_sequences')->insertOrIgnore([
                'prefixe' => 'EQP',
                'annee' => $annee,
                'last_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $suivant = (int) DB::table('stock_sequences')
                ->where('prefixe', 'EQP')
                ->where('annee', $annee)
                ->lockForUpdate()
                ->value('last_value') + 1;

            DB::table('stock_sequences')
                ->where('prefixe', 'EQP')
                ->where('annee', $annee)
                ->update(['last_value' => $suivant, 'updated_at' => now()]);

            return sprintf('EQP-%d-%04d', $annee, $suivant);
        });
    }
}
