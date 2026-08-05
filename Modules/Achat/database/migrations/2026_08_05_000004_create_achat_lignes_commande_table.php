<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 — les lignes portent la PHOTOGRAPHIE CONTRACTUELLE : designation,
     * nature, prix_unitaire_ht et taux_tva sont copiés du Catalogue à l'ajout
     * puis figés (IA-2). Ce n'est pas une duplication de référentiel
     * (EXI-INT-00) : c'est ce qui a été engagé, et cela ne doit plus bouger
     * même si le Catalogue change.
     *
     * `quantite_livree` est la source de vérité du reste à livrer (SFD §1.2),
     * alimentée transactionnellement par les réceptions Stock, bornée par la
     * base autant que par l'applicatif (IA-4).
     */
    public function up(): void
    {
        Schema::create('achat_lignes_commande', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_commande_id')->constrained('achat_bons_commande')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('catalogue_articles')->restrictOnDelete();

            // Valeurs FIGÉES à l'ajout (photographie contractuelle)
            $table->string('designation');
            $table->string('nature');
            $table->decimal('prix_unitaire_ht', 14, 2);
            $table->decimal('taux_tva', 5, 2);

            $table->decimal('quantite', 12, 2);
            $table->decimal('quantite_livree', 12, 2)->default(0);
            $table->decimal('montant_ht', 16, 2)->default(0);

            // Constat de service fait (lignes de prestation)
            $table->dateTime('service_fait_le')->nullable();
            $table->foreignId('service_fait_par')->nullable()->constrained('users')->nullOnDelete();
            $table->text('service_fait_commentaire')->nullable();

            $table->timestamps();

            $table->index('bon_commande_id');
            $table->index('article_id');
        });

        SchemaChecks::ajouter('achat_lignes_commande', [
            'chk_lignes_quantite_positive' => 'quantite > 0',
            // Le plafond de réception est doublé par la base : même en cas de
            // course, on ne peut pas livrer plus que commandé (IA-4).
            'chk_lignes_livree_bornee' => 'quantite_livree >= 0 AND quantite_livree <= quantite',
            'chk_lignes_prix_positif' => 'prix_unitaire_ht >= 0',
            'chk_lignes_tva_bornee' => 'taux_tva >= 0 AND taux_tva <= 100',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_lignes_commande');
    }
};
