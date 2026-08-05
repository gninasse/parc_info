<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raccordement Achat ⇄ Stock — PRQ-05 (`RACCORDEMENT_Achat_Stock.md` §6).
     *
     * Le bon d'entrée peut désormais être lié à un bon de commande : c'est
     * l'ancre structurelle du mode « Livraison sur commande ». Le lien
     * remplace l'usage détourné de `reference_externe`, qui retrouve son rôle
     * d'origine — le n° de BL papier du livreur (§2.3).
     *
     * `restrict` : un bon de commande ne peut pas s'évaporer sous les
     * réceptions qui l'ont soldé ; la chaîne d'audit ParcInfo → mouvement →
     * entrée → BC doit rester remontable (SFD Achat §1.2).
     *
     * Nullable : le mode libre reste entier (retours, régularisations, achats
     * hors module) — le raccordement ajoute un mode, il n'en supprime aucun.
     *
     * La migration vit dans le module Stock car elle amende SA table ; le
     * module Achat en dépend sans la posséder.
     */
    public function up(): void
    {
        // Le module Achat peut ne pas être installé : la colonne n'a alors
        // aucune cible et le raccordement reste inactif (dégradation propre).
        if (! Schema::hasTable('achat_bons_commande')) {
            return;
        }

        Schema::table('stock_entrees', function (Blueprint $table) {
            $table->foreignId('bon_commande_id')
                ->nullable()
                ->after('reference_externe')
                ->constrained('achat_bons_commande')
                ->restrictOnDelete();

            $table->index('bon_commande_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stock_entrees', 'bon_commande_id')) {
            return;
        }

        Schema::table('stock_entrees', function (Blueprint $table) {
            $table->dropForeign(['bon_commande_id']);
            $table->dropIndex(['bon_commande_id']);
            $table->dropColumn('bon_commande_id');
        });
    }
};
