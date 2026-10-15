<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->dateTimeTz('starts_at');
            $table->dateTimeTz('ends_at');
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();
            $table->index(['trainer_id', 'starts_at']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->dateTimeTz('starts_at');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('status', 20)->default('booked')->index(); // booked|completed|cancelled|no_show
            $table->text('meeting_url')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            // Partial-unique equivalent: enforce non-overlap in the service layer
            // within a transaction; plus index for conflict checks.
            $table->index(['trainer_id', 'starts_at']);
        });

        Schema::create('session_credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('delta'); // +credit purchase, -booking spend, +refund
            $table->string('reason', 100);
            $table->timestamps();
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_credit_ledger');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('trainer_availability');
    }
};
