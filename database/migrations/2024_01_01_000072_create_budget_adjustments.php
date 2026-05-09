<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('budget_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->decimal('transfer', 15, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn('transfer');
        });
        Schema::dropIfExists('budget_adjustment_items');
        Schema::dropIfExists('budget_adjustments');
    }
};
