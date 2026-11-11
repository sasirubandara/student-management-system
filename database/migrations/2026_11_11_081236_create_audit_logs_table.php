<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // User responsible for the activity.
            // Keep the audit record if that account is deleted.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Examples: student.created, user.role_updated.
            $table->string('event', 100);

            // Polymorphic relationship to the affected record.
            $table->nullableMorphs('auditable');

            // Carefully selected activity details.
            $table->json('properties')->nullable();

            $table->ipAddress('ip_address')->nullable();

            $table->text('user_agent')->nullable();

            // Audit logs are append-only, so no updated_at column.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};