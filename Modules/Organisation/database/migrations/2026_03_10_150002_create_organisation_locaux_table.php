<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_locaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etage_id')->constrained('organisation_etages')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->enum('type_local', ['bureau', 'salle_soins', 'salle_attente', 'magasin', 'couloir', 'autre'])->default('autre');
            $table->decimal('superficie_m2', 8, 2)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_locaux');
    }
};
