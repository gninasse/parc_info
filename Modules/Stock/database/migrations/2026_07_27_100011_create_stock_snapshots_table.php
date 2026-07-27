<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RG-F7-03 : snapshot immuable après création.
        Schema::create('stock_snapshots', function (Blueprint $table) {
            $table->id();
            // RG-F7-04/05 : YYYY-MM (mensuel) ou YYYY-MM-MANUEL-N.
            $table->string('reference', 30)->unique();
            $table->enum('type', ['MENSUEL', 'MANUEL']);
            $table->timestamp('date_snapshot');
            $table->decimal('valeur_totale_globale', 16, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_snapshots');
    }
};
