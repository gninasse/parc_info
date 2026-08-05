<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 — le bon de commande, colonne vertébrale du module.
     *
     * `numero` est nullable et attribué À LA VALIDATION seulement, sous verrou
     * (IA-3) : un brouillon n'a pas de numéro, sa suppression ne laisse aucun
     * trou. Les montants sont dénormalisés et calculés une seule fois côté
     * serveur à partir des lignes (IA-1). `fournisseur_libelle` et
     * `service_demandeur_libelle` sont des photographies : le document reste
     * lisible même si le référentiel bouge.
     */
    public function up(): void
    {
        Schema::create('achat_bons_commande', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->nullable()->unique(); // attribué à la validation
            $table->enum('statut', ['BROUILLON', 'SOUMIS', 'VALIDE', 'PARTIEL', 'LIVRE', 'CLOTURE', 'ANNULE'])
                ->default('BROUILLON');

            $table->foreignId('fournisseur_id')->constrained('catalogue_fournisseurs')->restrictOnDelete();
            $table->string('fournisseur_libelle')->nullable(); // dénormalisé à la validation

            $table->date('date_document');
            $table->boolean('est_regularisation')->default(false);

            // Service demandeur (A13) — prépare le circuit de demande v2
            $table->foreignId('service_demandeur_id')->nullable()
                ->constrained('organisation_services')->nullOnDelete();
            $table->string('service_demandeur_libelle')->nullable();
            $table->string('reference_demande')->nullable();

            // Observation typée (pilules de choix rapide — SPEC_UX A-03)
            $table->string('observation_type')->nullable();
            $table->text('observation_texte')->nullable();

            // Montants : calculés côté serveur, jamais en JS (IA-1)
            $table->decimal('montant_ht', 16, 2)->default(0);
            $table->decimal('montant_tva', 16, 2)->default(0);
            $table->decimal('montant_ttc', 16, 2)->default(0);

            $table->foreignId('soumis_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('soumis_le')->nullable();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('valide_le')->nullable();

            $table->text('motif_cloture')->nullable();
            $table->text('motif_annulation')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['statut', 'date_document']);
            $table->index('fournisseur_id');
            $table->index('numero');
        });

        SchemaChecks::ajouter('achat_bons_commande', [
            // Un numéro ne s'attribue qu'à la validation : un brouillon ou un
            // bon soumis n'en porte JAMAIS, un bon engagé en porte TOUJOURS.
            //
            // ANNULE est volontairement laissé libre : le SFD §1.4 le décrit
            // « jamais attribué », mais §7.5 n'autorise l'annulation que depuis
            // VALIDE, qui porte déjà un numéro — et le lui retirer creuserait un
            // trou dans la séquence (contraire à IA-3) tout en effaçant la trace
            // d'un document qui a pu circuler. La contrainte accepte donc les
            // deux cas et l'arbitrage est signalé à la MOA.
            'chk_bc_numero_si_engage' => "(numero IS NULL AND statut IN ('BROUILLON', 'SOUMIS'))"
                ." OR (numero IS NOT NULL AND statut IN ('VALIDE', 'PARTIEL', 'LIVRE', 'CLOTURE'))"
                ." OR statut = 'ANNULE'",
            'chk_bc_montants_positifs' => 'montant_ht >= 0 AND montant_tva >= 0 AND montant_ttc >= 0',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_bons_commande');
    }
};
