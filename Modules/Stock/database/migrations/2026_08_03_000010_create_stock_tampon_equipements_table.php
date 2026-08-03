<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 — tampon généralisé (D13/D14/D17) : exactement une des trois
     * FK de ligne (CHECK). Entrées : numero_serie saisi (unique tampon),
     * equipement_id renseigné à la validation. Sorties/transferts :
     * equipement_id pointé, unique dans tout le tampon — le tampon étant
     * purgé à la validation, l'unicité DB refuse d'elle-même une unité déjà
     * pointée dans un autre bon non validé. Vidé au retour brouillon.
     */
    public function up(): void
    {
        Schema::create('stock_tampon_equipements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_entree_id')->nullable()->constrained('stock_lignes_entrees')->cascadeOnDelete();
            $table->foreignId('ligne_sortie_id')->nullable()->constrained('stock_lignes_sorties')->cascadeOnDelete();
            $table->foreignId('ligne_transfert_id')->nullable()->constrained('stock_lignes_transferts')->cascadeOnDelete();
            $table->string('numero_serie')->nullable()->unique();
            $table->foreignId('equipement_id')->nullable()->unique()->constrained('parc_info_equipements')->restrictOnDelete();
            $table->timestamps();
        });

        SchemaChecks::ajouter('stock_tampon_equipements', [
            'chk_tampon_une_seule_ligne' => '(CASE WHEN ligne_entree_id IS NULL THEN 0 ELSE 1 END)'
                .' + (CASE WHEN ligne_sortie_id IS NULL THEN 0 ELSE 1 END)'
                .' + (CASE WHEN ligne_transfert_id IS NULL THEN 0 ELSE 1 END) = 1',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_tampon_equipements');
    }
};
