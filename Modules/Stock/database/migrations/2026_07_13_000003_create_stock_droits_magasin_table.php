<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_droits_magasin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('stock_magasins')->cascadeOnDelete();
            $table->enum('type_sujet', ['USER', 'ROLE']);
            $table->unsignedBigInteger('sujet_id');
            $table->boolean('peut_lire')->default(false);
            $table->boolean('peut_entrer_stock')->default(false);
            $table->boolean('peut_sortir_stock')->default(false);
            $table->boolean('peut_transferer')->default(false);
            $table->boolean('peut_inventorier')->default(false);
            $table->boolean('peut_administrer')->default(false);
            $table->timestamps();

            $table->unique(['magasin_id', 'type_sujet', 'sujet_id'], 'unique_magasin_sujet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_droits_magasin');
    }
};
