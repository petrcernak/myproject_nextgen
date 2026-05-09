<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('contract_items', function (Blueprint $table) {
            $table->foreignId('contract_category_id')->nullable()->after('contract_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\ContractCategory::class);
            $table->dropColumn('contract_category_id');
        });
        Schema::dropIfExists('contract_categories');
    }
};
