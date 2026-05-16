<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parc_info_consommables', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->foreignId('type_consommable_id')->constrained('parc_info_types_consommables')->onDelete('restrict');
            $table->foreignId('marque_id')->nullable()->constrained('parc_info_marques')->onDelete('set null');
            $table->string('modele_reference')->nullable();
            $table->json('compatible_equipements')->nullable();
            
            $table->foreignId('fournisseur_principal_id')->constrained('parc_info_fournisseurs')->onDelete('restrict');
            
            $table->decimal('cout_unitaire', 10, 2);
            $table->unsignedInteger('quantite_stock_actuel')->default(0);
            $table->unsignedInteger('quantite_stock_min')->default(5);
            $table->unsignedInteger('quantite_stock_max')->default(50);
            $table->unsignedInteger('stock_reserve_maintenance')->default(0);
            
            $table->date('date_dernier_approvisionnement')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['type_consommable_id', 'est_actif']);
            $table->index(['quantite_stock_actuel']);
            $table->index(['code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parc_info_consommables');
    }
};
