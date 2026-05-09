<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_anticipateds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_anticipated_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_anticipated_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_anticipated_items');
        Schema::dropIfExists('contract_anticipateds');
    }
};
