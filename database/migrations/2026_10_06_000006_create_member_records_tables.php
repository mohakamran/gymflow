<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->string('goal', 150)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('exercises')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'member_id']);
        });

        Schema::create('progress_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('recorded_on');
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('body_fat_percent', 5, 2)->nullable();
            $table->decimal('chest_cm', 6, 2)->nullable();
            $table->decimal('waist_cm', 6, 2)->nullable();
            $table->decimal('hips_cm', 6, 2)->nullable();
            $table->decimal('arms_cm', 6, 2)->nullable();
            $table->decimal('thighs_cm', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'recorded_on']);
        });

        Schema::create('member_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['member_id', 'created_at']);
        });

        Schema::create('member_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_documents');
        Schema::dropIfExists('member_notes');
        Schema::dropIfExists('progress_records');
        Schema::dropIfExists('workout_plans');
    }
};
