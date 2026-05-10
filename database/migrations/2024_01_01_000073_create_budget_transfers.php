<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->foreignId('from_budget_item_id')->constrained('budget_items')->cascadeOnDelete();
            $table->foreignId('to_budget_item_id')->constrained('budget_items')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn('transfer');
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->decimal('transfer', 15, 2)->default(0)->after('amount');
        });
        Schema::dropIfExists('budget_transfers');
    }
};
