<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title', 150);

            $table->string('category', 50);

            $table->string('original_name', 255);

            $table->string('file_path', 500)->unique();

            $table->string('mime_type', 127);

            $table->unsignedBigInteger('file_size');

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};