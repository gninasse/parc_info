<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parc_info_dictionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('parc_info_dictionnaire_valeurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dictionnaire_id')->constrained('parc_info_dictionnaires')->cascadeOnDelete();
            $table->string('valeur');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['dictionnaire_id', 'valeur']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parc_info_dictionnaire_valeurs');
        Schema::dropIfExists('parc_info_dictionnaires');
    }
};
