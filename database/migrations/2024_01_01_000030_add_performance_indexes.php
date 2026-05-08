<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('company_id');
            $table->index('created_at');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('contract_id');
            $table->index('status');
            $table->index('due');
            $table->index('issued');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('invoice_id');
        });

        Schema::table('contract_items', function (Blueprint $table) {
            $table->index('contract_id');
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->index('project_id');
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->index('budget_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', fn ($t) => $t->dropIndex(['project_id', 'company_id', 'created_at']));
        Schema::table('invoices', fn ($t) => $t->dropIndex(['contract_id', 'status', 'due', 'issued']));
        Schema::table('invoice_items', fn ($t) => $t->dropIndex(['invoice_id']));
        Schema::table('contract_items', fn ($t) => $t->dropIndex(['contract_id']));
        Schema::table('budgets', fn ($t) => $t->dropIndex(['project_id']));
        Schema::table('budget_items', fn ($t) => $t->dropIndex(['budget_category_id']));
    }
};
