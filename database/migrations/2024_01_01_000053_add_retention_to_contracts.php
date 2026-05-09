<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('retention_short', 5, 2)->nullable()->after('maturity');
            $table->decimal('retention_long',  5, 2)->nullable()->after('retention_short');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['retention_short', 'retention_long']);
        });
    }
};
