<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('contract_id')
                ->references('id')->on('contract_categories')->nullOnDelete();
        });

        Schema::table('budget_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('budget_id')
                ->references('id')->on('budget_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contract_categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });

        Schema::table('budget_categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
