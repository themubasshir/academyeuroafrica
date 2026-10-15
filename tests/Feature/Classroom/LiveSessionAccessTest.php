<?php

namespace Tests\Feature\Classroom;

use App\Models\Enrolment;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveSessionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_url_is_encrypted_at_rest(): void
    {
        $session = LiveSession::factory()->create(['meeting_url' => 'https://zoom.us/j/123']);

        $raw = \DB::table('live_sessions')->where('id', $session->id)->value('meeting_url');
        $this->assertNotSame('https://zoom.us/j/123', $raw);
        $this->assertSame('https://zoom.us/j/123', $session->fresh()->meeting_url);
    }

    public function test_enrolled_student_sees_course_session(): void
    {
        $session = LiveSession::factory()->create();
        $student = User::factory()->student()->create();
        Enrolment::factory()->create([
            'user_id' => $student->id,
            'course_variant_id' => \App\Models\CourseVariant::factory()
                ->for($session->course)->create()->id,
        ]);

        $this->assertTrue($session->visibleTo($student));
    }

    public function test_non_enrolled_student_cannot_see_course_session(): void
    {
        $session = LiveSession::factory()->create();
        $student = User::factory()->student()->create();

        $this->assertFalse($session->visibleTo($student));
    }

    public function test_group_session_visible_only_to_members(): void
    {
        $group = Group::factory()->create();
        $member = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();
        GroupMember::create(['group_id' => $group->id, 'user_id' => $member->id]);
        $session = LiveSession::factory()->for($group)->create();

        $this->assertTrue($session->visibleTo($member));
        $this->assertFalse($session->visibleTo($outsider));
    }

    public function test_individual_session_visible_only_to_that_student(): void
    {
        $student = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        $session = LiveSession::factory()->create(['student_id' => $student->id]);

        $this->assertTrue($session->visibleTo($student));
        $this->assertFalse($session->visibleTo($other));
    }

    public function test_hosting_trainer_sees_session(): void
    {
        $session = LiveSession::factory()->create();

        $this->assertTrue($session->visibleTo($session->trainer));
    }
}
