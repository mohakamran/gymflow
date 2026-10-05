<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
            $table->string('address_line')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 7)->default('#4f46e5');
            $table->string('currency', 3)->default('USD');
            $table->string('timezone', 64)->default('UTC');
            $table->string('locale', 10)->default('en');
            $table->json('business_hours')->nullable();
            $table->json('settings')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->string('subscription_plan', 30)->default('starter');
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('public_profile_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
