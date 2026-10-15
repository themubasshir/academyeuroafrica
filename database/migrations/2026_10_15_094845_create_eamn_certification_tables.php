<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Certification: per-course policy, issued certificates (immutable,
     * title/name snapshotted), approvals, trainer-marked attendance, audit.
     */
    public function up(): void
    {
        Schema::create('certification_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->boolean('require_lessons')->default(true);
            $table->boolean('require_quizzes')->default(true);
            $table->unsignedTinyInteger('min_quiz_percent')->nullable(); // override quiz's own pass mark
            $table->boolean('require_assignments')->default(true);
            $table->unsignedTinyInteger('min_assignment_percent')->nullable();
            $table->unsignedTinyInteger('min_attendance_percent')->nullable(); // null = attendance not required
            $table->boolean('require_trainer_approval')->default(false);
            $table->boolean('require_admin_approval')->default(false);
            $table->boolean('auto_issue')->default(true);
            $table->string('signatory_name')->nullable();
            $table->string('signatory_title')->nullable();
            $table->string('template_version', 30)->default('reg-v1');
            $table->timestamps();
        });

        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('attended')->default(true);
            $table->unsignedInteger('minutes')->nullable();
            $table->foreignId('marked_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('marked_at')->useCurrent();
            $table->timestamps();
            $table->unique(['live_session_id', 'user_id']);
        });

        Schema::create('enrolment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrolment_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10); // trainer|admin
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['enrolment_id', 'kind']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_ref', 40)->unique(); // random, non-sequential
            $table->string('number', 30)->unique();      // EAMN-2026-00001
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            // Snapshots — never rewritten after issue.
            $table->string('course_title');
            $table->string('recipient_name');
            $table->string('org_name')->default('EuroAfrica Media Academy');
            $table->string('signatory_name')->nullable();
            $table->string('signatory_title')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('completed_at');
            $table->string('template_version', 30)->default('reg-v1');
            $table->string('status', 20)->default('issued')->index(); // issued|revoked|superseded
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->foreignId('superseded_by')->nullable()->constrained('certificates')->nullOnDelete();
            $table->string('pdf_disk', 30)->default('local');
            $table->string('pdf_path')->nullable();
            // Idempotency: = enrolment_id while active, NULL after revoke/supersede.
            // unique() ignores NULLs → guarantees ≤1 active cert per enrolment.
            $table->unsignedBigInteger('active_key')->nullable()->unique();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('certificate_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('enrolment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40); // issued|revoked|reissued|approved|auto_skipped|failed
            $table->string('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_audits');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('enrolment_approvals');
        Schema::dropIfExists('session_attendances');
        Schema::dropIfExists('certification_policies');
    }
};
