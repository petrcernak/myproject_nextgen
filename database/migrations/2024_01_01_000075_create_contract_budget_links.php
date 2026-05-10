<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_budget_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->decimal('fx_rate', 12, 6)->nullable();
            $table->timestamps();
            $table->unique(['contract_id', 'budget_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_budget_links');
    }
};
