<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->string('regno', 50)->nullable();
            $table->string('taxregno', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('url', 255)->nullable();
            $table->foreignId('id_group')->nullable()->constrained('groups')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name');
            $table->string('status', 30)->default('active');
            $table->text('note')->nullable();
            $table->foreignId('id_company')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('id_group')->nullable()->constrained('groups')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_rights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('pright', 10)->default('r');
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name');
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->smallInteger('direction')->default(1);
            $table->string('currency', 10)->default('CZK');
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('maturity')->default(30);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('no', 100);
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sendby_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('sendto_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->text('description')->nullable();
            $table->date('issued')->nullable();
            $table->date('taxdate')->nullable();
            $table->date('due')->nullable();
            $table->date('paid')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('contract_items');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('user_rights');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('companies');
    }
};
