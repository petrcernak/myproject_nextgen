<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_group');
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['id_group', 'name']);
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->morphs('fileable');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('file_tag_file', function (Blueprint $table) {
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['file_id', 'file_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_tag_file');
        Schema::dropIfExists('files');
        Schema::dropIfExists('file_tags');
    }
};
