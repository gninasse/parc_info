<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Parametre;

/**
 * Numérotation (SFD §6.1) : `{PREFIXE}-{année}-{séquence sur 4 chiffres}`,
 * attribuée UNIQUEMENT à la validation, SOUS VERROU, remise à 1 au changement
 * d'année.
 *
 * Les brouillons ne consomment pas de numéro : leur suppression ne laisse
 * aucun trou (IA-3). Le verrou `lockForUpdate` sur la ligne de séquence
 * sérialise deux validations concurrentes, ce qui interdit la collision.
 *
 * Le numéro et l'engagement sont indissociables : porter un numéro, c'est
 * être engagé. La base l'impose (CHECK `chk_bc_numero_si_engage`) et ce
 * service l'applique en écrivant `numero` et `statut` d'un seul mouvement —
 * il n'existe aucun instant où un brouillon porterait un numéro.
 */
class NumerotationService
{
    /**
     * Numérote et engage le bon (BROUILLON|SOUMIS → VALIDE).
     *
     * À appeler DANS la transaction de validation, qui porte aussi les
     * dénormalisations et l'horodatage du visa (SFD §7.2).
     *
     * Idempotent : un bon déjà numéroté conserve son numéro et ne consomme
     * pas de séquence (rejeu d'une validation — IA-5).
     */
    public function attribuer(BonCommande $bon, ?int $validePar = null): string
    {
        if ($bon->numero !== null) {
            return $bon->numero;
        }

        $prefixe = Parametre::valeur(Parametre::PREFIXE_NUMEROTATION, 'BC');
        $numero = $this->prochainNumero($prefixe, now()->year);

        $bon->forceFill([
            'numero' => $numero,
            'statut' => BonCommande::STATUT_VALIDE,
            'valide_par' => $validePar ?? $bon->valide_par,
            'valide_le' => $bon->valide_le ?? now(),
        ])->save();

        return $numero;
    }

    /**
     * Consomme et renvoie le prochain numéro de la série, sous verrou.
     *
     * Exposé pour les cas où l'appelant compose lui-même l'écriture (reprise
     * de données, tests de concurrence) ; le contrat reste le même : ce qui
     * est consommé doit être posé sur un document engagé.
     */
    public function prochainNumero(string $prefixe, int $annee): string
    {
        DB::table('achat_sequences')->insertOrIgnore([
            'prefixe' => $prefixe,
            'annee' => $annee,
            'last_value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $suivant = (int) DB::table('achat_sequences')
            ->where('prefixe', $prefixe)
            ->where('annee', $annee)
            ->lockForUpdate()
            ->value('last_value') + 1;

        DB::table('achat_sequences')
            ->where('prefixe', $prefixe)
            ->where('annee', $annee)
            ->update(['last_value' => $suivant, 'updated_at' => now()]);

        return sprintf('%s-%d-%04d', $prefixe, $annee, $suivant);
    }
}
