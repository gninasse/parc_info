<?php

namespace Modules\Achat\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Génération de numéros de document séquentiels et annualisés.
 *
 * ENF-FIA-02 — La séquence est portée par une table dédiée et verrouillée
 * (lockForUpdate) le temps de la transaction : deux créations concurrentes ne
 * peuvent pas obtenir le même numéro. Le comptage des lignes existantes, non
 * sûr, n'est jamais utilisé.
 *
 * RG-NUM-01 / RG-NUM-02 — Format : {PREFIXE}-{ANNEE}-{SEQUENCE sur 4 chiffres}
 */
trait GeneratesDocumentNumbers
{
    public function genererNumero(string $type, string $prefix, ?int $annee = null): string
    {
        $annee ??= (int) date('Y');

        return DB::transaction(function () use ($type, $prefix, $annee) {
            $sequence = DB::table('achat_sequences')
                ->where('type', $type)
                ->where('annee', $annee)
                ->lockForUpdate()
                ->first();

            if ($sequence) {
                $compteur = $sequence->compteur + 1;

                DB::table('achat_sequences')
                    ->where('id', $sequence->id)
                    ->update(['compteur' => $compteur, 'updated_at' => now()]);
            } else {
                $compteur = 1;

                DB::table('achat_sequences')->insert([
                    'type' => $type,
                    'annee' => $annee,
                    'compteur' => $compteur,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return sprintf('%s-%d-%s', $prefix, $annee, str_pad((string) $compteur, 4, '0', STR_PAD_LEFT));
        });
    }
}
