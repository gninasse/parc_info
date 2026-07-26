<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paramétrage dynamique du module (EF-ADM-01 à EF-ADM-03).
 * Modifiable sans redéploiement via l'écran d'administration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achat_parametres', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 100)->unique();
            $table->text('valeur')->nullable();
            $table->string('libelle')->nullable();
            $table->string('description')->nullable();
            $table->enum('type_valeur', ['texte', 'entier', 'decimal', 'booleen'])->default('texte');
            $table->boolean('modifiable')->default(true);

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_parametres');
    }
};
