<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Contracts\ParcInfoIntegrationInterface;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Logiciel;

/**
 * Seul point de contact du module Achat avec les modèles ParcInfo.
 */
class ParcInfoIntegrationService implements ParcInfoIntegrationInterface
{
    public function existeNumeroSerie(string $numeroSerie): bool
    {
        return DB::table('parc_info_equipements')
            ->where('numero_serie', $numeroSerie)
            ->exists();
    }

    public function existeCodeInventaire(string $codeInventaire): bool
    {
        return DB::table('parc_info_equipements')
            ->where('code_inventaire', $codeInventaire)
            ->exists();
    }

    public function creerEquipement(array $donnees): int
    {
        // RG-INT-05 : un équipement acquis entre au parc en stock et en bon état.
        $equipement = Equipement::create([
            'categorie_id' => $donnees['categorie_id'],
            'code_inventaire' => $donnees['code_inventaire'],
            'numero_serie' => $donnees['numero_serie'],
            'marque_id' => $donnees['marque_id'],
            'modele' => $donnees['modele'],
            'date_acquisition' => $donnees['date_acquisition'],
            'valeur_achat' => $donnees['valeur_achat'],
            'ref_bordereau' => $donnees['ref_bordereau'],
            'statut' => 'en_stock',
            'etat' => 'bon',
            'champs_valeurs' => $donnees['champs_valeurs'] ?? [],
        ]);

        return $equipement->id;
    }

    public function historiserAcquisition(int $equipementId, int $userId, string $motif, string $referenceDocument): void
    {
        HistoriqueChangement::create([
            'equipement_id' => $equipementId,
            'date_changement' => now(),
            'utilisateur_id' => $userId,
            'type_changement' => 'acquisition',
            'nouveau_statut' => 'en_stock',
            'nouvel_etat' => 'bon',
            'motif' => $motif,
            'reference_document' => $referenceDocument,
        ]);
    }

    public function trouverOuCreerLogiciel(string $nom, string $code): int
    {
        $logiciel = Logiciel::firstOrCreate(
            ['nom' => $nom],
            [
                'code' => mb_substr($code, 0, 50),
                'est_actif' => true,
            ]
        );

        return $logiciel->id;
    }

    public function creerLicence(array $donnees): int
    {
        $licence = Licence::create([
            'logiciel_id' => $donnees['logiciel_id'],
            'cle_licence' => $donnees['cle_licence'],
            'date_acquisition' => $donnees['date_acquisition'],
            'date_activation' => $donnees['date_activation'],
            'date_expiration' => $donnees['date_expiration'] ?? null,
            'cout_unitaire' => $donnees['cout_unitaire'],
            'cout_total' => $donnees['cout_unitaire'],
            'fournisseur_id' => $donnees['fournisseur_id'],
            'statut' => 'VALIDE',
            'actif' => true,
            'notes' => $donnees['notes'] ?? null,
        ]);

        return $licence->id;
    }

    public function equipementsDesBordereaux(array $refsBordereaux): array
    {
        if ($refsBordereaux === []) {
            return [];
        }

        return Equipement::whereIn('ref_bordereau', $refsBordereaux)
            ->with(['categorie', 'marque'])
            ->get()
            ->map(fn (Equipement $equipement) => [
                'id' => $equipement->id,
                'code_inventaire' => $equipement->code_inventaire,
                'categorie_libelle' => $equipement->categorie?->libelle,
                'categorie_icone' => $equipement->categorie?->icone,
                'marque_libelle' => $equipement->marque?->libelle,
                'modele' => $equipement->modele,
                'numero_serie' => $equipement->numero_serie,
                'statut' => $equipement->statut,
                'etat' => $equipement->etat,
                'detail_route' => $equipement->detail_route,
            ])
            ->all();
    }

    public function champsDeCategorie(?int $categorieId): iterable
    {
        if (! $categorieId) {
            return [];
        }

        $categorie = CategorieEquipement::with('champs')->find($categorieId);

        return $categorie?->champs ?? [];
    }
}
