<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
            $table->unsignedInteger('quantite_livree')->default(0);
            $table->timestamps();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE achat_lignes_commande ADD CONSTRAINT chk_quantite_positive CHECK (quantite > 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achat_lignes_commande');
    }
};
