<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('annee', 4);
            $table->unsignedInteger('compteur')->default(0);
            $table->timestamps();

            $table->unique(['type', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sequences');
    }
};
