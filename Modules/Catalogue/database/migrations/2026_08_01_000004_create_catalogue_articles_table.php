<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_articles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->enum('nature', ['consommable', 'piece', 'equipement', 'licence']);
            $table->boolean('est_stockable');
            $table->foreignId('categorie_id')->constrained('catalogue_categories')->onDelete('restrict');
            $table->foreignId('marque_id')->nullable()->constrained('parc_info_marques')->onDelete('set null');
            $table->string('reference_constructeur')->nullable();
            $table->string('unite_stock')->default('unité');
            $table->decimal('prix_indicatif', 14, 2)->nullable();
            $table->decimal('taux_tva', 5, 2)->default(18.00);
            $table->decimal('seuil_defaut', 10, 2)->nullable();
            $table->foreignId('fournisseur_principal_id')->nullable()->constrained('catalogue_fournisseurs')->onDelete('set null');
            $table->foreignId('categorie_equipement_id')->nullable()->constrained('parc_info_categories_equipements')->onDelete('restrict');
            $table->foreignId('logiciel_id')->nullable()->constrained('parc_info_logiciels')->onDelete('restrict');
            $table->jsonb('compatibilites')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['marque_id', 'reference_constructeur']);
        });

        // SQLite n'accepte pas l'ajout de CHECK a posteriori : sur ce driver, la
        // règle est portée par la validation applicative du modèle Article
        // (testée), doublon exigé par le SFD §6.1.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE catalogue_articles ADD CONSTRAINT chk_articles_categorie_equipement
                CHECK (
                    (nature = 'equipement' AND categorie_equipement_id IS NOT NULL)
                    OR (nature <> 'equipement' AND categorie_equipement_id IS NULL)
                )
            ");
            DB::statement("
                ALTER TABLE catalogue_articles ADD CONSTRAINT chk_articles_logiciel
                CHECK (
                    (nature = 'licence' AND logiciel_id IS NOT NULL)
                    OR (nature <> 'licence' AND logiciel_id IS NULL)
                )
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_articles');
    }
};
