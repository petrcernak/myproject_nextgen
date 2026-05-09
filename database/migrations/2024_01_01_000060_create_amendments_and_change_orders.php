<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 255);
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('change_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amendment_id')->nullable()->constrained('amendments')->nullOnDelete();
            $table->string('code', 20);
            $table->string('name', 255);
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('change_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_order_items');
        Schema::dropIfExists('change_orders');
        Schema::dropIfExists('amendments');
    }
};
