<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Achat\Models\Parametre;

/**
 * Paramétrage initial du module (EF-ADM-01 à EF-ADM-03).
 *
 * Les valeurs existantes ne sont jamais écrasées : ce seeder est rejouable
 * sans perdre le paramétrage propre à l'établissement.
 */
class ParametresSeeder extends Seeder
{
    public function run(): void
    {
        $parametres = [
            [
                'cle' => 'prefix_bon_commande',
                'valeur' => config('achat.prefix_bon_commande', 'BC'),
                'libelle' => 'Préfixe des bons de commande',
                'description' => 'Préfixe utilisé dans la numérotation : {PREFIXE}-{ANNEE}-{SEQUENCE}.',
                'type_valeur' => 'texte',
            ],
            [
                'cle' => 'prefix_bordereau_livraison',
                'valeur' => config('achat.prefix_bordereau_livraison', 'BL'),
                'libelle' => 'Préfixe des bordereaux de livraison',
                'description' => 'Préfixe utilisé dans la numérotation : {PREFIXE}-{ANNEE}-{SEQUENCE}.',
                'type_valeur' => 'texte',
            ],
            [
                'cle' => 'pattern_code_inventaire',
                'valeur' => config('achat.code_inventaire_pattern', 'INV-{YYYY}-{SEQUENCE:4}'),
                'libelle' => 'Format des codes inventaire',
                'description' => 'Variables acceptées : {YYYY}, {YY}, {SEQUENCE} et {SEQUENCE:n}.',
                'type_valeur' => 'texte',
            ],
            [
                'cle' => 'taux_tva_defaut',
                'valeur' => (string) config('achat.taux_tva_defaut', 18),
                'libelle' => 'Taux de TVA par défaut (%)',
                'description' => 'Proposé au référencement d\'un article. Le taux appliqué à une commande est celui de l\'article.',
                'type_valeur' => 'decimal',
            ],
            [
                'cle' => 'reliquat_alerte_jours',
                'valeur' => (string) config('achat.reliquat_alerte_jours', 60),
                'libelle' => 'Ancienneté d\'alerte des reliquats (jours)',
                'description' => 'Au-delà de ce délai, les bons partiellement livrés sont signalés sur le tableau de bord.',
                'type_valeur' => 'entier',
            ],
        ];

        foreach ($parametres as $parametre) {
            $existant = Parametre::where('cle', $parametre['cle'])->first();

            if ($existant) {
                // Les libellés évoluent avec le code, la valeur reste celle de l'établissement.
                $existant->update([
                    'libelle' => $parametre['libelle'],
                    'description' => $parametre['description'],
                    'type_valeur' => $parametre['type_valeur'],
                ]);

                continue;
            }

            Parametre::create($parametre + ['modifiable' => true]);
        }
    }
}
