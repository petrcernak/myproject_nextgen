<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->boolean('vtp_auto')->default(true)->after('anticipated_manual');
            $table->dropColumn('value_to_place_manual');
        });

        Schema::create('budget_item_vtps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_item_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_item_vtps');
        Schema::table('budget_items', function (Blueprint $table) {
            $table->decimal('value_to_place_manual', 15, 2)->nullable()->after('anticipated_manual');
            $table->dropColumn('vtp_auto');
        });
    }
};
