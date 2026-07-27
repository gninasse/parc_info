<?php

namespace Modules\Stock\Services;

use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\AffectationLicence;
use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\MouvementConsommable;
use Modules\ParcInfo\Models\TypeConsommable;
use Modules\Stock\Contracts\ParcInfoIntegrationInterface;
use Modules\Stock\Exceptions\RegleMetierException;

/**
 * Seul point de contact du module Stock avec les modèles ParcInfo.
 *
 * Appelé dans la transaction de la sortie (RG-F4-02/03) : toute exception
 * levée ici annule la sortie entière. Aucune écriture de compteur de
 * quantité ParcInfo : le module Stock est le référentiel unique (EF-STK-05).
 */
class ParcInfoIntegrationService implements ParcInfoIntegrationInterface
{
    public function affecterEquipement(array $donnees): int
    {
        $equipement = Equipement::find($donnees['equipement_id']);

        if (! $equipement) {
            throw new RegleMetierException("L'équipement ciblé est introuvable dans le parc.");
        }

        $affectation = AffectationEquipement::create(array_merge(
            [
                'code' => 'AFF-STK-'.strtoupper(uniqid()),
                'equipement_id' => $equipement->id,
                'statut' => true,
                'type_affectation' => 'PERMANENTE',
                'date_debut' => now()->toDateString(),
            ],
            $this->mapperCible($donnees['type_cible'], (int) $donnees['cible_id'])
        ));

        // RG ParcInfo — l'affectation d'un équipement en stock le met en service.
        $ancienStatut = $equipement->statut;

        if ($ancienStatut === 'en_stock') {
            $equipement->update(['statut' => 'en_service']);
        }

        HistoriqueChangement::create([
            'equipement_id' => $equipement->id,
            'date_changement' => now(),
            'utilisateur_id' => $donnees['user_id'],
            'type_changement' => 'AFFECTATION',
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $equipement->statut,
            'motif' => $donnees['motif'] ?? 'Affectation via sortie de stock',
        ]);

        return $affectation->id;
    }

    public function tracerConsommation(array $donnees): int
    {
        $consommable = Consommable::where('code', $donnees['code_article'])->first();

        if (! $consommable) {
            // Fiche minimale de traçabilité — aucune quantité n'y est portée.
            // Type et fournisseur génériques : le schéma ParcInfo les exige.
            $type = TypeConsommable::firstOrCreate(
                ['code' => 'GEN-CONS'],
                [
                    'nom' => 'Consommables divers',
                    'categorie' => 'Accessoires',
                    'unite_stock' => 'Unité',
                    'seul_reapprovisionnement' => 5,
                ]
            );

            $fournisseur = Fournisseur::firstOrCreate(
                ['code' => 'FRS-DIVERS'],
                ['nom' => 'Fournisseur divers', 'est_actif' => true]
            );

            $consommable = Consommable::create([
                'code' => $donnees['code_article'],
                'nom' => $donnees['designation'],
                'type_consommable_id' => $type->id,
                'fournisseur_principal_id' => $fournisseur->id,
                'cout_unitaire' => 0,
                'est_actif' => true,
            ]);
        }

        $mouvement = MouvementConsommable::create([
            'consommable_id' => $consommable->id,
            'type_mouvement' => 'Consommation',
            'quantite' => $donnees['quantite'],
            'date_mouvement' => now(),
            'reference_commande' => $donnees['reference_document'] ?? null,
            'utilisateur_id' => $donnees['user_id'],
            'employe_id' => $donnees['type_cible'] === 'EMPLOYE' ? $donnees['cible_id'] : null,
            'raison' => $donnees['motif'] ?? 'Sortie de stock',
        ]);

        return $mouvement->id;
    }

    public function affecterLicence(array $donnees): int
    {
        $licence = Licence::find($donnees['licence_id']);

        if (! $licence) {
            throw new RegleMetierException('La licence ciblée est introuvable dans le parc.');
        }

        if ($donnees['type_cible'] !== 'EMPLOYE') {
            throw new RegleMetierException('Une licence ne peut être affectée qu\'à un employé.');
        }

        $affectation = AffectationLicence::create([
            'licence_id' => $licence->id,
            'employe_id' => $donnees['cible_id'],
            'type_affectation' => 'PERMANENTE',
            'date_affectation' => now()->toDateString(),
            'actif' => true,
            'notes' => $donnees['motif'] ?? 'Affectation via sortie de stock',
        ]);

        return $affectation->id;
    }

    /**
     * Traduit la cible générique (type + id) vers les colonnes de
     * parc_info_affectation_equipements.
     *
     * @return array<string, mixed>
     */
    protected function mapperCible(string $typeCible, int $cibleId): array
    {
        return match ($typeCible) {
            'EMPLOYE' => ['type_cible' => 'EMPLOYE', 'dossier_employe_id' => $cibleId],
            'POSTE' => ['type_cible' => 'POSTE', 'poste_travail_id' => $cibleId],
            'DIRECTION' => ['type_cible' => 'DIRECTION', 'niveau_rattachement' => 'DIRECTION', 'direction_id' => $cibleId],
            'SERVICE' => ['type_cible' => 'SERVICE', 'niveau_rattachement' => 'SERVICE', 'service_id' => $cibleId],
            'UNITE' => ['type_cible' => 'UNITE', 'niveau_rattachement' => 'UNITE', 'unite_id' => $cibleId],
            default => throw new RegleMetierException("Type de cible {$typeCible} inconnu."),
        };
    }
}
