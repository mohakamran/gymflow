<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_value');
            $table->string('duration_unit', 10);
            $table->decimal('price', 12, 2);
            $table->decimal('signup_fee', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->json('features')->nullable();
            $table->unsignedSmallInteger('class_limit_per_week')->nullable();
            $table->string('access_hours', 100)->nullable();
            $table->string('color', 7)->default('#4f46e5');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
