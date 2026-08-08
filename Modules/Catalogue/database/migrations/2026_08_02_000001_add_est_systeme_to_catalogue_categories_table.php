<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * C7 : les catégories « Non classé » (une par nature) sont seedées et ne
     * doivent pas être supprimables. Le choix retenu est un drapeau explicite
     * est_systeme plutôt qu'une liste de codes en dur : la garde de
     * CategorieController::destroy s'appuie dessus, et de futures catégories
     * protégées n'exigeront aucun changement de code.
     */
    public function up(): void
    {
        Schema::table('catalogue_categories', function (Blueprint $table) {
            $table->boolean('est_systeme')->default(false)->after('est_actif');
        });
    }

    public function down(): void
    {
        Schema::table('catalogue_categories', function (Blueprint $table) {
            $table->dropColumn('est_systeme');
        });
    }
};
