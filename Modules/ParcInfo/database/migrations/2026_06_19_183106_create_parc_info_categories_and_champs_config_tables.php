<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parc_info_categories_equipements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->string('icone')->nullable();
            $table->timestamps();
        });

        Schema::create('parc_info_champs_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('parc_info_categories_equipements')->cascadeOnDelete();
            $table->string('code');
            $table->string('libelle');
            $table->string('type_champ'); // text, number, select, boolean, date, textarea
            $table->string('source_options')->nullable(); // DICT:code_dict, etc.
            $table->string('regles_validation')->nullable();
            $table->string('nom_panel')->default('Général');
            $table->integer('ordre_affichage')->default(0);
            $table->boolean('afficher_dans_modal')->default(true);
            $table->boolean('afficher_dans_show')->default(true);
            $table->boolean('afficher_dans_liste')->default(false);
            $table->integer('ordre_colonne_liste')->default(99);
            $table->timestamps();

            $table->unique(['categorie_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parc_info_champs_config');
        Schema::dropIfExists('parc_info_categories_equipements');
    }
};
