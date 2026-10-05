<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('renewed_from_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20);
            $table->decimal('price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->date('suspended_on')->nullable();
            $table->date('cancelled_on')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('expiry_reminder_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'ends_on']);
            $table->index(['member_id', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
