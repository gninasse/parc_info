<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achat_lignes_commande', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_de_commande_id')
                ->constrained('achat_bons_commande')
                ->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('achat_articles');

            $table->unsignedInteger('quantite');
            $table->decimal('prix_unitaire', 12, 2);

            // RG-BC-05 — Le taux est figé à la création de la ligne : une
            // évolution ultérieure du taux de l'article ne doit pas modifier
            // rétroactivement le montant d'un bon de commande déjà validé.
            $table->decimal('taux_tva', 5, 2)->default(0);

            $table->unsignedInteger('quantite_livree')->default(0);

            $table->timestamps();

            $table->index('bon_de_commande_id');
            $table->unique(['bon_de_commande_id', 'article_id'], 'unique_article_par_bc');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE achat_lignes_commande ADD CONSTRAINT chk_lc_quantite_positive CHECK (quantite > 0)');
            DB::statement('ALTER TABLE achat_lignes_commande ADD CONSTRAINT chk_lc_livree_coherente CHECK (quantite_livree >= 0 AND quantite_livree <= quantite)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_lignes_commande');
    }
};
