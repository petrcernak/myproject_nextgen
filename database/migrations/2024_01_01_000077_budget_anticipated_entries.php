<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_anticipated_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_item_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn('anticipated_manual');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_anticipated_entries');
        Schema::table('budget_items', function (Blueprint $table) {
            $table->decimal('anticipated_manual', 15, 2)->default(0);
        });
    }
};
