<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id', 'creator_id', 'title', 'instructions', 'due_at', 'max_points',
    'audience', 'group_id', 'status', 'grades_released',
])]
class Assignment extends Model
{
    use HasFactory;

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assignment_recipients')->withTimestamps();
    }

    /** @return HasMany<AssignmentSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /**
     * Is this user a target recipient? Whole-course → enrolled; group → member;
     * individual → in recipients table.
     */
    public function isRecipient(User $user): bool
    {
        return match ($this->audience) {
            'students' => \App\Models\AssignmentRecipient::where('assignment_id', $this->id)
                ->where('user_id', $user->id)->exists(),
            'group' => $this->group !== null
                && GroupMember::where('group_id', $this->group_id)->where('user_id', $user->id)->exists(),
            default => Enrolment::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereHas('variant', fn ($q) => $q->where('course_id', $this->course_id))
                ->exists(),
        };
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'grades_released' => 'boolean',
        ];
    }
}
