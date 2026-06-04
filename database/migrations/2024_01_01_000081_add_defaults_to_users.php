<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('default_page_type', 20)->nullable();
            $table->unsignedBigInteger('default_page_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_project_id']);
            $table->dropColumn(['default_project_id', 'default_page_type', 'default_page_id']);
        });
    }
};
