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
        Schema::create('achat_articles', function (Blueprint $table) {
            $table->id();
            $table->string('code_article', 50)->unique();
            $table->string('designation');
            $table->text('description')->nullable();
            $table->enum('type_article', ['equipement', 'consommable', 'licence', 'prestation'])
                ->default('equipement');
            $table->string('reference_constructeur', 100)->nullable();

            // Foreign Keys to ParcInfo tables
            $table->foreignId('marque_id')->constrained('parc_info_marques');
            $table->foreignId('categorie_equipement_id')->nullable()
                ->constrained('parc_info_categories_equipements');
            $table->foreignId('fournisseur_prefere_id')->nullable()
                ->constrained('parc_info_fournisseurs');

            $table->decimal('prix_indicatif', 12, 2)->default(0);
            $table->string('unite_mesure', 20)->default('unite');
            $table->decimal('taux_tva', 5, 2)->default(20.00);
            $table->string('compte_comptable', 20)->nullable();
            $table->unsignedInteger('seuil_alerte')->default(0);
            $table->unsignedInteger('stock_actuel')->default(0);
            $table->unsignedInteger('duree_validite_mois')->nullable();
            $table->string('url_fiche_technique', 500)->nullable();
            $table->string('image', 500)->nullable();
            $table->boolean('actif')->default(true);

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['type_article', 'categorie_equipement_id']);
            $table->index('actif');

            // Unique composite constraint (reference constructeur unique par marque)
            $table->unique(['marque_id', 'reference_constructeur'], 'unique_ref_marque');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achat_articles');
    }
};
