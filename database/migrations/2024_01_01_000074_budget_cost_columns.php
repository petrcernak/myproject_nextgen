<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->foreignId('budget_item_id')
                  ->nullable()
                  ->after('contract_category_id')
                  ->constrained('budget_items')
                  ->nullOnDelete();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('fx_rate', 12, 6)->nullable()->after('currency');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('fx_rate', 12, 6)->nullable()->after('status');
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->decimal('anticipated_manual', 15, 2)->default(0)->after('amount');
            $table->decimal('value_to_place_manual', 15, 2)->nullable()->after('anticipated_manual');
        });
    }

    public function down(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropForeign(['budget_item_id']);
            $table->dropColumn('budget_item_id');
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('fx_rate');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('fx_rate');
        });
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn(['anticipated_manual', 'value_to_place_manual']);
        });
    }
};
