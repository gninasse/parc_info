<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Support\SchemaChecks;

return new class extends Migration
{
    /**
     * SFD §6.2 — en-tête de session du wizard de réception des licences (A-05).
     * `FINALISEE` = les licences existent dans ParcInfo ; `ABANDONNEE` = retour
     * en arrière tracé. La FK est `restrict` : une session finalisée est une
     * trace, elle ne disparaît pas avec la ligne.
     */
    public function up(): void
    {
        Schema::create('achat_receptions_licences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_commande_id')->constrained('achat_lignes_commande')->restrictOnDelete();
            $table->decimal('quantite', 12, 2);
            $table->enum('statut', ['EN_COURS', 'FINALISEE', 'ABANDONNEE'])->default('EN_COURS');
            $table->dateTime('finalisee_le')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('ligne_commande_id');
        });

        SchemaChecks::ajouter('achat_receptions_licences', [
            'chk_receptions_quantite_positive' => 'quantite > 0',
            'chk_receptions_finalisee_horodatee' => "(statut <> 'FINALISEE' AND finalisee_le IS NULL)"
                ." OR (statut = 'FINALISEE' AND finalisee_le IS NOT NULL)",
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_receptions_licences');
    }
};
