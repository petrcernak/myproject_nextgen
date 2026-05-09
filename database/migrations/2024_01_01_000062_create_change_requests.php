<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('change_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_item_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('change_request_item_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_item_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount_supplier', 15, 2)->default(0);
            $table->decimal('amount_pm', 15, 2)->default(0);
            $table->decimal('amount_report', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_item_revisions');
        Schema::dropIfExists('change_request_items');
        Schema::dropIfExists('change_requests');
    }
};
