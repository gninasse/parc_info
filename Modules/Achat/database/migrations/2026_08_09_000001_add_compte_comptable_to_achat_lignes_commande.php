<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * D-24 — le compte comptable FIGÉ sur la ligne de commande.
     *
     * Même doctrine que le prix et le taux de TVA (IA-2) : ce qui est
     * photographié à la saisie ne bouge plus. La raison est la même, et elle
     * est comptable avant d'être technique — si l'imputation était lue en
     * temps réel depuis le Catalogue, réaffecter un article à un autre compte
     * réécrirait **rétroactivement** l'imputation de toutes les commandes
     * passées, y compris celles d'exercices clos.
     *
     * Un état des dépenses par compte doit dire ce qui a été imputé au moment
     * de l'engagement, pas ce qu'on imputerait aujourd'hui.
     *
     * Nullable : les lignes créées avant P0-B, et celles dont l'article n'a
     * pas encore d'imputation, restent parfaitement valides — elles seront
     * comptées sous « Non imputé », jamais masquées.
     */
    public function up(): void
    {
        if (Schema::hasColumn('achat_lignes_commande', 'compte_comptable')) {
            return;
        }

        Schema::table('achat_lignes_commande', function (Blueprint $table) {
            $table->string('compte_comptable', 50)->nullable()->after('taux_tva');
            $table->index('compte_comptable', 'idx_lignes_compte_comptable');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('achat_lignes_commande', 'compte_comptable')) {
            return;
        }

        Schema::table('achat_lignes_commande', function (Blueprint $table) {
            $table->dropIndex('idx_lignes_compte_comptable');
            $table->dropColumn('compte_comptable');
        });
    }
};
