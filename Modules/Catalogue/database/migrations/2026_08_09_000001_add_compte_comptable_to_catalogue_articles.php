<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P0-B (PRQ-03) — le compte comptable d'imputation.
     *
     * L'établissement doit pouvoir répondre à « combien avons-nous dépensé
     * sur tel compte cette année ? ». Aujourd'hui la question n'a pas de
     * réponse : rien ne relie un article à une ligne du plan comptable.
     *
     * Trois partis pris :
     *
     *   - **format libre en v1**. Le plan comptable de l'établissement n'est
     *     pas encore arrêté dans l'application ; imposer un format
     *     maintenant reviendrait à choisir à la place du service financier,
     *     et à bloquer la saisie le jour où il choisira autrement ;
     *   - **nullable partout**. Aucun flux existant ne doit s'arrêter parce
     *     qu'un article n'a pas encore son imputation. La colonne se remplit
     *     progressivement, article par article ;
     *   - **indexée**. Sa seule raison d'être est d'agréger des montants par
     *     compte (l'état par imputation, D-24) : sans index, ce futur écran
     *     balaierait tout le catalogue à chaque ouverture.
     */
    public function up(): void
    {
        if (Schema::hasColumn('catalogue_articles', 'compte_comptable')) {
            return;
        }

        Schema::table('catalogue_articles', function (Blueprint $table) {
            $table->string('compte_comptable', 50)->nullable()->after('taux_tva');
            $table->index('compte_comptable', 'idx_articles_compte_comptable');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('catalogue_articles', 'compte_comptable')) {
            return;
        }

        Schema::table('catalogue_articles', function (Blueprint $table) {
            $table->dropIndex('idx_articles_compte_comptable');
            $table->dropColumn('compte_comptable');
        });
    }
};
