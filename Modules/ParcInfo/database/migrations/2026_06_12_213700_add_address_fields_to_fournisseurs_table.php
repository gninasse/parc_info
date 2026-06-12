<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parc_info_fournisseurs', function (Blueprint $table) {
            $table->string('code_postal', 10)->nullable()->after('adresse');
            $table->string('ville', 100)->nullable()->after('code_postal');
            $table->string('pays', 100)->nullable()->after('ville');
        });
    }

    public function down(): void
    {
        Schema::table('parc_info_fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['code_postal', 'ville', 'pays']);
        });
    }
};
