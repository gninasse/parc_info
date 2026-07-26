<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('type'); // MENSUEL, MANUEL
            $table->timestamp('date_snapshot');
            $table->decimal('valeur_totale_globale', 16, 2)->default(0.00);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_snapshots');
    }
};
