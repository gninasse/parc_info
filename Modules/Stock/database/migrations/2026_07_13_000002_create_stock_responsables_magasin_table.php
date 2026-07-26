<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_responsables_magasin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->cascadeOnDelete();
            $table->foreignId('employe_id')->nullable()->constrained('grh_dossiers_employes')->nullOnDelete();
            $table->enum('role', ['principal', 'adjoint'])->default('principal');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_responsables_magasin');
    }
};
