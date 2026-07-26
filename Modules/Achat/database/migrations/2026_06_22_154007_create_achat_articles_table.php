<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achat_articles', function (Blueprint $table) {
            $table->id();
            $table->string('code_article', 50)->unique();
            $table->string('designation');
            $table->text('description')->nullable();
            $table->enum('type_article', ['equipement', 'consommable', 'licence', 'prestation'])
                ->default('equipement');
            $table->string('reference_constructeur', 100)->nullable();

            // Référentiels détenus par le module ParcInfo (EXI-INT-00)
            $table->foreignId('marque_id')->constrained('parc_info_marques');
            $table->foreignId('categorie_equipement_id')->nullable()
                ->constrained('parc_info_categories_equipements');
            $table->foreignId('fournisseur_prefere_id')->nullable()
                ->constrained('parc_info_fournisseurs');

            $table->decimal('prix_indicatif', 12, 2)->default(0);
            $table->string('unite_mesure', 20)->default('Unité');

            // RG-BC-05 : taux proposé par défaut, figé sur la ligne à la commande
            $table->decimal('taux_tva', 5, 2)->default(18.00);

            $table->string('compte_comptable', 20)->nullable();
            $table->unsignedInteger('seuil_alerte')->default(0);

            // EF-STK-05 : projection dénormalisée entretenue par le service
            // d'intégration. Le référentiel faisant foi est le module Stock.
            $table->unsignedInteger('stock_actuel')->default(0);

            $table->unsignedInteger('duree_validite_mois')->nullable();
            $table->string('url_fiche_technique', 500)->nullable();
            $table->string('image', 500)->nullable();
            $table->boolean('actif')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type_article', 'categorie_equipement_id']);
            $table->index('actif');
            $table->unique(['marque_id', 'reference_constructeur'], 'unique_ref_marque');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_articles');
    }
};
