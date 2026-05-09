<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_advance')->default(false)->after('note');
            $table->decimal('advance_amount', 15, 2)->nullable()->after('is_advance');
        });

        Schema::create('invoice_advance_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('advance_invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_advance_deductions');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['is_advance', 'advance_amount']);
        });
    }
};
