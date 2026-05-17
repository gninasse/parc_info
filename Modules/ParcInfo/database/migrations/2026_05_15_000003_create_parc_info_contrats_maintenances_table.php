<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_contrats_maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('nom')->nullable();
            $table->foreignId('fournisseur_id')->nullable()->constrained('parc_info_fournisseurs')->onDelete('set null');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->decimal('cout', 10, 2)->nullable();
            $table->boolean('est_actif')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_contrats_maintenances');
    }
};
