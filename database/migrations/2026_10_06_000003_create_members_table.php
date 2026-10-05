<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('member_code', 20);
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('emergency_contact_name', 120)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->string('photo_path')->nullable();
            $table->date('joined_on');
            $table->string('status', 20)->default('active');
            $table->string('check_in_token', 64)->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'member_code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'last_name', 'first_name']);
            $table->index(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
