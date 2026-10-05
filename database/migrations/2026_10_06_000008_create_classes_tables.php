<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#4f46e5');
            $table->unsignedSmallInteger('capacity')->default(20);
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('location', 100)->nullable();
            $table->boolean('allow_member_booking')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gym_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('series_id')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedSmallInteger('capacity');
            $table->string('location', 100)->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'starts_at']);
            $table->index('series_id');
        });

        Schema::create('class_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('booked');
            $table->timestamps();

            $table->unique(['class_session_id', 'member_id']);
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_bookings');
        Schema::dropIfExists('class_sessions');
        Schema::dropIfExists('gym_classes');
    }
};
