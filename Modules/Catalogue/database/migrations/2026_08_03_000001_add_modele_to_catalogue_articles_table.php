<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogue_articles', function (Blueprint $table) {
            $table->string('modele')->nullable()->after('marque_id');
        });
    }

    public function down(): void
    {
        Schema::table('catalogue_articles', function (Blueprint $table) {
            $table->dropColumn('modele');
        });
    }
};
