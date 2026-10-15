<?php

namespace Tests\Feature;

use App\Domain\Certification\CertificateIssuanceService;
use App\Domain\Certification\CertificationEligibilityService;
use App\Models\Certificate;
use App\Models\CertificationPolicy;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\EnrolmentApproval;
use App\Models\Lesson;
use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificationTest extends TestCase
{
    use RefreshDatabase;

    private function course(array $policy = []): Course
    {
        $course = Course::factory()->create();
        CertificationPolicy::create(array_merge([
            'course_id' => $course->id,
            'enabled' => true,
            'require_lessons' => true,
            'require_quizzes' => false,
            'require_assignments' => false,
            'auto_issue' => true,
        ], $policy));

        return $course;
    }

    private function enrol(Course $course, ?User $u = null): Enrolment
    {
        $student = $u ?? User::factory()->student()->create();
        $variant = $course->variants()->first()
            ?? CourseVariant::factory()->for($course)->create();

        return Enrolment::firstOrCreate(
            ['user_id' => $student->id, 'course_variant_id' => $variant->id],
            ['status' => 'active', 'enrolled_at' => now()]
        );
    }

    private function completeLessons(Enrolment $e): void
    {
        $course = $e->variant->course;
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'M', 'sort_order' => 1, 'status' => 'published']);
        $lesson = Lesson::create(['course_module_id' => $module->id, 'title' => 'L', 'type' => 'text', 'sort_order' => 1, 'status' => 'published']);
        $e->lessonProgress()->updateOrCreate(['lesson_id' => $lesson->id], ['completed_at' => now()]);
    }

    private function eligibleEnrolment(Course $course): Enrolment
    {
        $e = $this->enrol($course);
        $this->completeLessons($e);

        return $e;
    }

    public function test_eligible_student_gets_certificate(): void
    {
        Storage::fake('local');
        $e = $this->eligibleEnrolment($this->course());
        $cert = app(CertificateIssuanceService::class)->issueIfEligible($e);
        $this->assertInstanceOf(Certificate::class, $cert);
        $this->assertStringStartsWith('EAMN-2026-', $cert->number);
        $this->assertEquals($e->variant->course->title, $cert->course_title);
        $this->assertEquals($e->user->name, $cert->recipient_name);
        $this->assertNotNull($cert->pdf_path);
    }

    public function test_ineligible_student_no_certificate(): void
    {
        $e = $this->enrol($this->course()); // no lessons done
        $r = app(CertificateIssuanceService::class)->issueIfEligible($e);
        $this->assertIsArray($r);
        $this->assertSame('missing', $r['status']);
        $this->assertDatabaseMissing('certificates', ['enrolment_id' => $e->id]);
    }

    public function test_certification_disabled_returns_disabled(): void
    {
        $e = $this->enrol($this->course(['enabled' => false]));
        $r = app(CertificationEligibilityService::class)->evaluate($e);
        $this->assertSame('disabled', $r['status']);
    }

    public function test_trainer_approval_blocks_until_approved(): void
    {
        $e = $this->eligibleEnrolment($this->course(['require_trainer_approval' => true]));
        $r = app(CertificationEligibilityService::class)->evaluate($e);
        $this->assertSame('pending_approval', $r['status']);

        EnrolmentApproval::create(['enrolment_id' => $e->id, 'kind' => 'trainer', 'approver_id' => User::factory()->trainer()->create()->id]);
        $r = app(CertificationEligibilityService::class)->evaluate($e->fresh());
        $this->assertSame('eligible', $r['status']);
    }

    public function test_duplicate_issue_returns_same_certificate(): void
    {
        Storage::fake('local');
        $e = $this->eligibleEnrolment($this->course());
        $svc = app(CertificateIssuanceService::class);
        $a = $svc->issueIfEligible($e);
        $b = $svc->issueIfEligible($e);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Certificate::where('enrolment_id', $e->id)->count());
    }

    public function test_manual_issue_when_auto_off(): void
    {
        Storage::fake('local');
        $e = $this->eligibleEnrolment($this->course(['auto_issue' => false]));
        $svc = app(CertificateIssuanceService::class);
        $r = $svc->issueIfEligible($e);
        $this->assertIsArray($r); // not auto-issued
        $cert = $svc->issueIfEligible($e, auth()->user(), force: true);
        $this->assertInstanceOf(Certificate::class, $cert);
    }

    public function test_revoke_flips_status_and_allows_reissue(): void
    {
        Storage::fake('local');
        $e = $this->eligibleEnrolment($this->course());
        $svc = app(CertificateIssuanceService::class);
        $cert = $svc->issueIfEligible($e);
        $admin = User::factory()->superAdmin()->create();

        $svc->revoke($cert->fresh(), $admin, 'Test revocation');
        $this->assertSame('revoked', $cert->fresh()->status);
        $this->assertNotNull($cert->fresh()->revocation_reason);

        $new = $svc->reissue($cert->fresh(), $admin, 'Correction');
        $this->assertSame('issued', $new->status);
        $this->assertSame('superseded', $cert->fresh()->status);
        $this->assertSame($new->id, $cert->fresh()->superseded_by);
        $this->assertSame(1, Certificate::where('enrolment_id', $e->id)->whereNotNull('active_key')->count());
    }

    public function test_student_only_downloads_own_certificate(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('certificates/x.pdf', 'x');
        $e = $this->eligibleEnrolment($this->course());
        $cert = app(CertificateIssuanceService::class)->issueIfEligible($e);

        $other = User::factory()->student()->create();
        $this->actingAs($other)->get(route('student.certificates.download', $cert))->assertForbidden();
        $this->actingAs($e->user)->get(route('student.certificates.download', $cert))->assertOk();
    }

    public function test_public_verify_valid_and_revoked_and_unknown(): void
    {
        Storage::fake('local');
        $e = $this->eligibleEnrolment($this->course());
        $cert = app(CertificateIssuanceService::class)->issueIfEligible($e);

        $this->get("/verify/{$cert->public_ref}")->assertOk()->assertSee('Valid credential')->assertSee($e->user->name)->assertSee($cert->number);

        app(CertificateIssuanceService::class)->revoke($cert->fresh(), User::factory()->superAdmin()->create(), 'X');
        $this->get("/verify/{$cert->public_ref}")->assertOk()->assertSee('revoked', false);

        $this->get('/verify/'.str_repeat('a', 32))->assertNotFound();
        $this->get('/verify/EAMN-2026-00001')->assertNotFound(); // sequential id not a ref
        $this->get('/verify/short')->assertNotFound();
    }

    public function test_certificate_pdf_renders_real_file(): void
    {
        Storage::fake('local');
        $e = $this->eligibleEnrolment($this->course());
        $cert = app(CertificateIssuanceService::class)->issueIfEligible($e);
        $this->assertTrue(Storage::disk('local')->exists($cert->pdf_path));
        $this->assertGreaterThan(50_000, Storage::disk('local')->size($cert->pdf_path));
        $this->assertStringContainsString('academy.euroafricamedia.eu', $cert->verificationUrl());
    }

    public function test_snapshot_preserves_course_title_after_rename(): void
    {
        Storage::fake('local');
        $e = $this->eligibleEnrolment($this->course());
        $cert = app(CertificateIssuanceService::class)->issueIfEligible($e);
        $original = $cert->course_title;
        $e->variant->course->update(['title' => 'RENAMED COURSE']);
        $this->assertSame($original, $cert->fresh()->course_title);
    }

    public function test_failed_quiz_blocks_when_required(): void
    {
        $course = $this->course(['require_quizzes' => true, 'require_lessons' => false]);
        $e = $this->enrol($course);
        $quiz = Quiz::create(['course_id' => $course->id, 'creator_id' => User::factory()->trainer()->create()->id, 'title' => 'Q', 'passing_score' => 70, 'status' => 'published']);
        QuizAttempt::create(['quiz_id' => $quiz->id, 'user_id' => $e->user_id, 'attempt_no' => 1, 'status' => 'graded', 'score' => 40]);

        $r = app(CertificationEligibilityService::class)->evaluate($e);
        $this->assertSame('missing', $r['status']);
        $this->assertFalse($r['checks']['quizzes']['done']);
    }

    public function test_attendance_requirement_blocks_when_unmet(): void
    {
        Storage::fake('local');
        $course = $this->course(['require_lessons' => false, 'min_attendance_percent' => 50]);
        $e = $this->enrol($course);
        $trainer = User::factory()->trainer()->create();
        $session = LiveSession::create(['course_id' => $course->id, 'trainer_id' => $trainer->id, 'title' => 'S', 'starts_at' => now()->subDay(), 'status' => 'scheduled', 'provider' => 'zoom', 'duration_minutes' => 60]);

        $r = app(CertificationEligibilityService::class)->evaluate($e);
        $this->assertSame('missing', $r['status']);

        SessionAttendance::create(['live_session_id' => $session->id, 'user_id' => $e->user_id, 'attended' => true, 'marked_by' => $trainer->id]);
        $r = app(CertificationEligibilityService::class)->evaluate($e->fresh());
        $this->assertTrue($r['checks']['attendance']['done']);
    }
}
