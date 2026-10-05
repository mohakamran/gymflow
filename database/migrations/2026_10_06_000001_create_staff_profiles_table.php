<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('job_title', 100)->nullable();
            $table->string('specialization', 150)->nullable();
            $table->text('bio')->nullable();
            $table->date('hired_on')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->json('working_hours')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
