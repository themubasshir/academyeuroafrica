<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assignment_submission_id', 'grader_id', 'score', 'passed', 'feedback', 'status'])]
class AssignmentGrade extends Model
{
    use HasFactory;

    /** @return BelongsTo<AssignmentSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'assignment_submission_id');
    }

    /** @return BelongsTo<User, $this> */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grader_id');
    }

    protected function casts(): array
    {
        return ['passed' => 'boolean'];
    }
}
