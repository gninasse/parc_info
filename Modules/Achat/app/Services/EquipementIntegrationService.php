<?php

namespace Modules\Achat\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BordereauLivraison;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;

class EquipementIntegrationService
{
    /**
     * Crée une fiche équipement dans ParcInfo à partir d'une unité reçue.
     *
     * @throws Exception
     */
    public function creerEquipement(Article $article, array $unitData, BordereauLivraison $bordereau, float $prixUnitaire, int $userId): Equipement
    {
        $numeroSerie = trim($unitData['numero_serie'] ?? '');
        $codeInventaire = trim($unitData['code_inventaire'] ?? '');
        $champsValeurs = $unitData['champs_valeurs'] ?? [];

        // 1. Validation d'unicité
        if (empty($numeroSerie)) {
            throw new Exception("Le numéro de série est obligatoire pour l'article : {$article->designation}");
        }

        if (empty($codeInventaire)) {
            throw new Exception("Le code inventaire est obligatoire pour l'article : {$article->designation}");
        }

        $existsSerie = DB::table('parc_info_equipements')->where('numero_serie', $numeroSerie)->exists();
        if ($existsSerie) {
            throw new Exception("Le numéro de série '{$numeroSerie}' existe déjà dans le parc.");
        }

        $existsInventaire = DB::table('parc_info_equipements')->where('code_inventaire', $codeInventaire)->exists();
        if ($existsInventaire) {
            throw new Exception("Le code inventaire '{$codeInventaire}' existe déjà dans le parc.");
        }

        // 2. Création de l'équipement
        return DB::transaction(function () use ($article, $numeroSerie, $codeInventaire, $champsValeurs, $bordereau, $prixUnitaire, $userId) {
            $equipement = Equipement::create([
                'categorie_id' => $article->categorie_equipement_id,
                'code_inventaire' => $codeInventaire,
                'numero_serie' => $numeroSerie,
                'marque_id' => $article->marque_id,
                'modele' => $article->designation,
                'date_acquisition' => $bordereau->date_livraison,
                'valeur_achat' => $prixUnitaire,
                'ref_bordereau' => $bordereau->numero_livraison,
                'statut' => 'en_stock',
                'etat' => 'bon',
                'champs_valeurs' => $champsValeurs,
            ]);

            // 3. Historisation de l'acquisition
            HistoriqueChangement::create([
                'equipement_id' => $equipement->id,
                'date_changement' => now(),
                'utilisateur_id' => $userId,
                'type_changement' => 'acquisition',
                'nouveau_statut' => 'en_stock',
                'nouvel_etat' => 'bon',
                'motif' => "Acquisition automatique via la validation du BL n° {$bordereau->numero_livraison} (BC n° {$bordereau->bonCommande->numero_commande})",
                'reference_document' => $bordereau->numero_livraison,
            ]);

            return $equipement;
        });
    }
}
