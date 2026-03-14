<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_etages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batiment_id')->constrained('organisation_batiments')->cascadeOnDelete();
            $table->integer('numero');
            $table->string('libelle');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['batiment_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_etages');
    }
};
