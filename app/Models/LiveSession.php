<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

#[Fillable([
    'course_id', 'trainer_id', 'group_id', 'student_id', 'title', 'description',
    'starts_at', 'timezone', 'duration_minutes', 'provider', 'meeting_url',
    'recording_url', 'status',
])]
class LiveSession extends Model
{
    use HasFactory;

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** Meeting URLs are sensitive — encrypt at rest, decrypt on read. */
    protected function meetingUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Can this user see the meeting link? Enrolled (or group/student-targeted)
     * students plus the hosting trainer and admins.
     */
    public function visibleTo(User $user): bool
    {
        if ($user->isAdmin() || $this->trainer_id === $user->id) {
            return true;
        }

        // Student-targeted session.
        if ($this->student_id !== null) {
            return $this->student_id === $user->id;
        }

        // Group session.
        if ($this->group_id !== null) {
            return GroupMember::where('group_id', $this->group_id)
                ->where('user_id', $user->id)->exists();
        }

        // Whole-course session → any active enrolment on any variant.
        return Enrolment::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('variant', fn ($q) => $q->where('course_id', $this->course_id))
            ->exists();
    }

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }
}
