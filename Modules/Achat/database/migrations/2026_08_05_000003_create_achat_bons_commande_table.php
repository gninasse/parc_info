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

        // Définition centralisée : ces CHECK sont reposés par toute
        // migration ultérieure qui modifie la structure de la table (sur
        // SQLite, un ADD COLUMN les perdrait silencieusement).
        SchemaChecks::ajouter('achat_bons_commande', SchemaChecks::bonsCommande());
    }

    public function down(): void
    {
        /*
         * Le raccordement PRQ-05 pose, depuis le module Stock, une clé
         * étrangère `stock_entrees.bon_commande_id` vers cette table. Tant
         * qu'elle existe, PostgreSQL refuse le DROP (« dependent objects still
         * exist ») et le rollback d'Achat échoue.
         *
         * On dénoue donc le lien avant de retirer la table. C'est bien à
         * Achat de le faire : c'est SA table qui disparaît, et un module doit
         * pouvoir se désinstaller sans exiger qu'on démonte d'abord un autre
         * module. La colonne, elle, reste — elle appartient à Stock, qui la
         * retirera par sa propre migration.
         */
        if (Schema::hasColumn('stock_entrees', 'bon_commande_id')) {
            Schema::table('stock_entrees', function (Blueprint $table) {
                $table->dropForeign(['bon_commande_id']);
            });
        }

        Schema::dropIfExists('achat_bons_commande');
    }
};
