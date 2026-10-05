<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('category', 30);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->date('purchased_on')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('location', 100)->nullable();
            $table->string('condition', 20)->default('good');
            $table->string('status', 20)->default('active');
            $table->date('last_maintained_on')->nullable();
            $table->date('next_maintenance_on')->nullable();
            $table->timestamp('maintenance_reminder_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'next_maintenance_on']);
        });

        Schema::create('equipment_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->date('performed_on');
            $table->string('type', 30);
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('performed_by', 150)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenances');
        Schema::dropIfExists('equipment');
    }
};
