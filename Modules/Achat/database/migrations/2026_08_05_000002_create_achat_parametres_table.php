<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paramètres métier du module (SFD §6.2) — écran A-08 obligatoire
     * (leçon AN-13/14) ; `config/` reste réservé aux constantes techniques.
     */
    public function up(): void
    {
        Schema::create('achat_parametres', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();
            $table->text('valeur')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_parametres');
    }
};
