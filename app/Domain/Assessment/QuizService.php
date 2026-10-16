<?php

namespace App\Domain\Assessment;

use App\Domain\Certification\CertificateTrigger;
use App\Models\Enrolment;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Server-side quiz engine: timing, attempt limits, auto-grading, result release.
 * Correct answers are never exposed before the configured release condition.
 */
class QuizService
{
    /** Start an attempt — enforces attempt limits and enrolment. */
    public function startAttempt(Quiz $quiz, User $user): QuizAttempt
    {
        abort_unless($quiz->status === 'published', 403);
        abort_unless($this->enrolled($quiz, $user), 403, 'Not enrolled in this course.');

        return DB::transaction(function () use ($quiz, $user) {
            $existing = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $user->id);
            abort_if((clone $existing)->where('status', 'in_progress')->exists(), 422, 'Attempt already in progress.');

            $count = (clone $existing)->count();
            abort_if($count >= $quiz->max_attempts, 422, 'Attempt limit reached.');

            $questionIds = $quiz->questions()->pluck('id')->all();
            if ($quiz->randomise_questions) {
                shuffle($questionIds);
            }

            return QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'attempt_no' => $count + 1,
                'started_at' => now(),
                'status' => 'in_progress',
                'question_order' => $questionIds,
            ]);
        });
    }

    /** Record or replace a single answer within the time window. */
    public function answer(QuizAttempt $attempt, int $questionId, array $optionIds = [], ?string $text = null): void
    {
        abort_unless(! auth()->check() || auth()->id() === $attempt->user_id, 403);
        abort_unless($this->enrolled($attempt->quiz, $attempt->user), 403, 'Course access is no longer active.');
        abort_unless($attempt->status === 'in_progress', 422, 'Attempt is closed.');
        abort_if($attempt->isExpired(), 422, 'Time limit exceeded.');
        abort_unless(in_array($questionId, $attempt->question_order ?? [], true), 422, 'Question not part of this attempt.');

        QuizAnswer::updateOrCreate(
            ['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $questionId],
            ['chosen_option_ids' => $optionIds ?: null, 'text_answer' => $text]
        );
    }

    /**
     * Submit — auto-grades objective questions, flags manual ones for review.
     * Returns the attempt; score withheld until release rules allow.
     */
    public function submit(QuizAttempt $attempt): QuizAttempt
    {
        abort_unless(! auth()->check() || auth()->id() === $attempt->user_id, 403);
        abort_unless($this->enrolled($attempt->quiz, $attempt->user), 403, 'Course access is no longer active.');
        abort_unless($attempt->status === 'in_progress', 422, 'Attempt is closed.');

        return DB::transaction(function () use ($attempt) {
            $total = 0;
            $needsManual = false;

            foreach ($attempt->quiz->questions()->with('options')->get() as $question) {
                $answer = $attempt->answers()->where('quiz_question_id', $question->id)->first();

                if ($question->isAutoGraded()) {
                    $awarded = $this->gradeObjective($question, $answer);
                    $answer?->update(['awarded_points' => $awarded]);
                    $total += $awarded;
                } else {
                    $needsManual = true;
                }
            }

            $attempt->update([
                'submitted_at' => now(),
                'score' => $needsManual ? null : $total,
                'status' => $needsManual ? 'submitted' : 'graded',
            ]);

            // Auto-graded → may unlock certification. Find the student's
            // enrolment on this course and re-check eligibility.
            if (! $needsManual) {
                $enrolment = Enrolment::where('user_id', $attempt->user_id)
                    ->where('status', 'active')
                    ->whereHas('variant', fn ($q) => $q->where('course_id', $attempt->quiz->course_id))
                    ->first();
                if ($enrolment) {
                    CertificateTrigger::for($enrolment);
                }
            }

            return $attempt->fresh();
        });
    }

    /** Award points for auto-graded question types. */
    private function gradeObjective($question, ?QuizAnswer $answer): int
    {
        if ($answer === null) {
            return 0;
        }

        $correctIds = $question->options->where('is_correct', true)->pluck('id')->sort()->values()->all();

        return match ($question->type) {
            'mcq', 'tf' => ($answer->chosen_option_ids ?? []) === $correctIds ? $question->points : 0,
            'multi' => collect($answer->chosen_option_ids ?? [])->sort()->values()->all() === $correctIds ? $question->points : 0,
            'short' => $this->gradeShort($question, $answer),
            default => 0,
        };
    }

    /** Short-answer: match against any correct option label (case-insensitive trim). */
    private function gradeShort($question, QuizAnswer $answer): int
    {
        $acceptable = $question->options->where('is_correct', true)
            ->pluck('label')->map(fn ($s) => mb_strtolower(trim($s)));

        return $acceptable->contains(mb_strtolower(trim((string) $answer->text_answer)))
            ? $question->points : 0;
    }

    /** Can the student view their result now, per release_results? */
    public function resultsVisible(QuizAttempt $attempt): bool
    {
        return match ($attempt->quiz->release_results) {
            'immediate' => $attempt->status !== 'in_progress',
            'after_close' => $attempt->status === 'graded',
            'manual' => $attempt->status === 'graded',
            default => false,
        };
    }

    private function enrolled(Quiz $quiz, User $user): bool
    {
        return Enrolment::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereHas('variant', fn ($q) => $q->where('course_id', $quiz->course_id))
            ->exists();
    }
}
