<?php

namespace App\Domain\Certification;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrolment;
use App\Models\EnrolmentApproval;
use App\Models\LiveSession;
use App\Models\QuizAttempt;
use App\Models\SessionAttendance;

/**
 * Server-side evaluation of an enrolment against the course's certification
 * policy. Never trusts browser-submitted progress — every check queries
 * authoritative records scoped to this enrolment/course/user.
 */
class CertificationEligibilityService
{
    /**
     * Returns a structured result:
     *   status:  disabled | eligible | pending_approval | missing
     *   checks:  keyed requirement → ['required','done','detail','met','total']
     *   percent: 0–100 completion across required checks.
     */
    public function evaluate(Enrolment $enrolment): array
    {
        $course = $enrolment->variant->course;
        $policy = $course->certificationPolicy;

        if (! $policy?->enabled) {
            return ['status' => 'disabled', 'checks' => [], 'percent' => null, 'policy' => null];
        }

        $user = $enrolment->user;
        $checks = [];

        // Enrolment must be live (expired blocks everything).
        $expired = ! $enrolment->isActive();
        $checks['enrolment'] = [
            'required' => true,
            'done' => ! $expired,
            'detail' => $expired ? 'Enrolment expired' : 'Active enrolment',
        ];

        if ($policy->require_lessons) {
            $publishedLessonCount = $course->lessons()->where('lessons.status', 'published')->count();
            $requiredLessonIds = $course->lessons()
                ->where('lessons.status', 'published')
                ->where('lessons.is_required', true)
                ->pluck('lessons.id');
            $total = $requiredLessonIds->count();
            $met = $enrolment->lessonProgress()
                ->whereIn('lesson_id', $requiredLessonIds)
                ->whereNotNull('completed_at')
                ->count();
            $checks['lessons'] = [
                'required' => true, 'done' => $publishedLessonCount > 0 && $met >= $total,
                'met' => $met, 'total' => $total,
                'detail' => "$met of $total lessons completed",
            ];
        }

        if ($policy->require_quizzes) {
            $quizzes = $course->quizzes()->where('status', 'published')->where('is_required', true)->get();
            $passed = 0;
            foreach ($quizzes as $quiz) {
                $threshold = $policy->min_quiz_percent ?? $quiz->passing_score;
                // Best graded attempt counts; in_progress/submitted-ungraded don't.
                $best = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'graded')->max('score') ?? -1;
                if ($threshold !== null && $best >= $threshold) {
                    $passed++;
                }
            }
            $checks['quizzes'] = [
                'required' => true, 'done' => $quizzes->isEmpty() || $passed === $quizzes->count(),
                'met' => $passed, 'total' => $quizzes->count(),
                'detail' => "$passed of {$quizzes->count()} quizzes passed",
            ];
        }

        if ($policy->require_assignments) {
            $assignments = $course->assignments()->where('status', 'published')->where('is_required', true)->get();
            $passed = 0;
            foreach ($assignments as $a) {
                if ($this->assignmentPassed($a, $user->id, $policy->min_assignment_percent)) {
                    $passed++;
                }
            }
            $checks['assignments'] = [
                'required' => true, 'done' => $assignments->isEmpty() || $passed === $assignments->count(),
                'met' => $passed, 'total' => $assignments->count(),
                'detail' => "$passed of {$assignments->count()} assignments passed",
            ];
        }

        if ($policy->min_attendance_percent !== null) {
            // Denominator: past sessions the student was invited to
            // (course-wide, group they belong to, or individually targeted).
            $sessions = LiveSession::where('course_id', $course->id)
                ->where('status', '!=', 'cancelled')
                ->where('starts_at', '<=', now())->get()
                ->filter(fn ($s) => $s->visibleTo($user));
            $total = $sessions->count();
            $attended = $total === 0 ? 0 : SessionAttendance::where('user_id', $user->id)
                ->where('attended', true)
                ->whereIn('live_session_id', $sessions->pluck('id'))->count();
            $pct = $total === 0 ? 100 : (int) round($attended / $total * 100);
            $checks['attendance'] = [
                'required' => true, 'done' => $pct >= $policy->min_attendance_percent,
                'met' => $attended, 'total' => $total,
                'detail' => "$pct% attendance (need {$policy->min_attendance_percent}%)",
            ];
        }

        if ($policy->require_trainer_approval) {
            $ok = EnrolmentApproval::where('enrolment_id', $enrolment->id)->where('kind', 'trainer')->exists();
            $checks['trainer_approval'] = [
                'required' => true, 'done' => $ok,
                'detail' => $ok ? 'Trainer approved' : 'Awaiting trainer approval',
            ];
        }

        if ($policy->require_admin_approval) {
            $ok = EnrolmentApproval::where('enrolment_id', $enrolment->id)->where('kind', 'admin')->exists();
            $checks['admin_approval'] = [
                'required' => true, 'done' => $ok,
                'detail' => $ok ? 'Administrator approved' : 'Awaiting administrator approval',
            ];
        }

        $required = collect($checks)->where('required');
        $done = $required->where('done', true)->count();
        $percent = $required->isEmpty() ? 100 : (int) round($done / $required->count() * 100);

        $missing = $required->where('done', false);
        $onlyApprovalMissing = $missing->keys()->every(fn ($k) => str_ends_with($k, '_approval'));

        return [
            'status' => $missing->isEmpty() ? 'eligible'
                : ($onlyApprovalMissing ? 'pending_approval' : 'missing'),
            'checks' => $checks,
            'percent' => $percent,
            'policy' => $policy,
        ];
    }

    private function assignmentPassed(Assignment $a, int $userId, ?int $minPercent): bool
    {
        $submission = AssignmentSubmission::where('assignment_id', $a->id)
            ->where('user_id', $userId)
            ->whereHas('grade', fn ($query) => $query->where('status', 'released'))
            ->with('grade')
            ->latest('attempt_no')
            ->first();

        if (! $submission?->grade || $submission->grade->status !== 'released') {
            return false; // ungraded, draft or returned grades block
        }

        if ($a->evaluation_type === 'pass_fail') {
            return $submission->grade->passed === true;
        }

        $max = max(1, $a->max_points);
        $pct = $submission->grade->score / $max * 100;

        return $pct >= ($minPercent ?? $a->passing_score ?? 50);
    }
}
