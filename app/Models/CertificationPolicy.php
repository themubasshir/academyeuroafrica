<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'course_id', 'enabled', 'require_lessons', 'require_quizzes',
    'min_quiz_percent', 'require_assignments', 'min_assignment_percent',
    'min_attendance_percent', 'require_trainer_approval', 'require_admin_approval',
    'auto_issue', 'signatory_name', 'signatory_title', 'template_version',
])]
class CertificationPolicy extends Model
{
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'require_lessons' => 'boolean',
            'require_quizzes' => 'boolean',
            'require_assignments' => 'boolean',
            'require_trainer_approval' => 'boolean',
            'require_admin_approval' => 'boolean',
            'auto_issue' => 'boolean',
        ];
    }
}
