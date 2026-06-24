<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\Parametre;

class CodeInventaireGeneratorService
{
    /**
     * Génère le prochain code inventaire unique.
     */
    public function generer(): string
    {
        $pattern = Parametre::getVal('pattern_code_inventaire', 'INV-{YYYY}-{SEQUENCE:4}');

        return DB::transaction(function () use ($pattern) {
            // Verrouiller la ligne pour éviter les accès concurrents
            $compteurParam = Parametre::where('cle', 'compteur_inventaire_annee')->lockForUpdate()->first();

            if (! $compteurParam) {
                $compteurParam = Parametre::create([
                    'cle' => 'compteur_inventaire_annee',
                    'valeur' => '0',
                    'description' => 'Dernier numéro séquentiel de code inventaire utilisé',
                ]);
            }

            $anneeEnCours = date('Y');
            $sequence = (int) $compteurParam->valeur;

            do {
                $sequence++;
                $code = $this->appliquerPattern($pattern, $anneeEnCours, $sequence);

                // Vérifier l'unicité dans la table parc_info_equipements
                $exists = DB::table('parc_info_equipements')
                    ->where('code_inventaire', $code)
                    ->exists();

            } while ($exists);

            // Mettre à jour le compteur en DB
            $compteurParam->update(['valeur' => (string) $sequence]);

            return $code;
        });
    }

    /**
     * Applique les variables de remplacement sur le pattern.
     */
    protected function appliquerPattern(string $pattern, string $annee, int $sequence): string
    {
        $code = str_replace('{YYYY}', $annee, $pattern);

        // Chercher {SEQUENCE:X}
        if (preg_match('/\{SEQUENCE:(\d+)\}/', $code, $matches)) {
            $padding = (int) $matches[1];
            $seqStr = str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);
            $code = str_replace($matches[0], $seqStr, $code);
        } else {
            $code = str_replace('{SEQUENCE}', (string) $sequence, $code);
        }

        return $code;
    }
}
