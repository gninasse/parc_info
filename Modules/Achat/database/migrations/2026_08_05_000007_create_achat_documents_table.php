<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 — pièces jointes avec « PIERRE TOMBALE » (A16) : après
     * validation, supprimer une pièce efface le fichier physique mais CONSERVE
     * la ligne, motivée et signée. Une pièce gênante ne disparaît pas
     * silencieusement (IA-13).
     */
    public function up(): void
    {
        Schema::create('achat_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_commande_id')->constrained('achat_bons_commande')->cascadeOnDelete();
            $table->enum('type', ['bc_signe', 'bordereau_fournisseur', 'facture_proforma', 'autre']);

            $table->string('chemin')->nullable(); // vidé quand le fichier est effacé
            $table->string('nom_original');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('taille')->nullable();

            // Pierre tombale
            $table->boolean('est_supprime')->default(false);
            $table->foreignId('supprime_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('supprime_le')->nullable();
            $table->text('motif_suppression')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['bon_commande_id', 'est_supprime']);
        });

        SchemaChecks::ajouter('achat_documents', [
            // Une pierre tombale est toujours motivée et signée : sans cela,
            // la trace ne vaudrait rien (A16).
            'chk_documents_pierre_tombale_motivee' => '(est_supprime = '.self::faux().' AND motif_suppression IS NULL AND supprime_le IS NULL)'
                .' OR (est_supprime = '.self::vrai().' AND motif_suppression IS NOT NULL AND supprime_le IS NOT NULL)',
        ]);
    }

    /**
     * PostgreSQL type les booléens (true/false), SQLite les stocke en 0/1 :
     * l'expression du CHECK doit s'adapter au pilote (SQL portable, SFD §1.7).
     */
    private static function vrai(): string
    {
        return \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'true' : '1';
    }

    private static function faux(): string
    {
        return \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'false' : '0';
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_documents');
    }
};
