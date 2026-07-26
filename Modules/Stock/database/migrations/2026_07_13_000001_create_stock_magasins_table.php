<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_magasins', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('nom', 255);
            $table->enum('type', ['TECHNIQUE', 'CONSOMMABLE', 'REBUT']);
            $table->text('description')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_magasins');
    }
};
