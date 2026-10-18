<?php

namespace Tests\Feature;

use App\Domain\Checkout\OrderService;
use App\Livewire\Admin\Courses;
use App\Models\Course;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DirectCheckoutLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_and_returns_to_course_checkout_link(): void
    {
        [$course, $variant] = $this->assignedCourse();
        $checkoutUrl = route('checkout.course', ['course' => $course->slug]);

        $this->get($checkoutUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $checkoutUrl);

        $this->assertSame($course->checkout_variant_id, session('bridge_intent.variant_id'));

        $this->get(route('bridge.start'))->assertRedirect();
        $this->assertSame(route('checkout.show', $variant), session('url.intended'));
    }

    public function test_authenticated_student_is_sent_to_assigned_package_checkout(): void
    {
        $student = User::factory()->student()->create();
        [$course, $variant] = $this->assignedCourse();

        $this->actingAs($student)
            ->get(route('checkout.course', ['course' => $course->slug]))
            ->assertRedirect(route('checkout.show', $variant));
    }

    public function test_direct_link_rejects_unpublished_course_or_unavailable_package(): void
    {
        $student = User::factory()->student()->create();
        [$draftCourse] = $this->assignedCourse(['status' => 'draft']);
        [$course, $draftVariant] = $this->assignedCourse([], ['status' => 'draft']);

        $this->actingAs($student)
            ->get(route('checkout.course', ['course' => $draftCourse->slug]))
            ->assertNotFound();

        $this->actingAs($student)
            ->get(route('checkout.course', ['course' => $course->slug]))
            ->assertNotFound();

        $this->assertFalse($draftVariant->isPurchasable());
    }

    public function test_administrator_assigns_package_and_sees_copyable_link(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        $course = Course::factory()->create();
        $variant = CourseVariant::factory()->for($course)->create();

        Livewire::actingAs($admin)
            ->test(Courses::class)
            ->call('assignCheckoutPackage', $course->id, $variant->id)
            ->assertHasNoErrors();

        $this->assertSame($variant->id, $course->fresh()->checkout_variant_id);

        $this->actingAs($admin)
            ->get(route('admin.courses'))
            ->assertOk()
            ->assertSee('Copy Checkout Link')
            ->assertSee($course->slug);
    }

    public function test_active_enrolment_prevents_another_payment(): void
    {
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create();
        Enrolment::factory()->for($student)->for($variant, 'variant')->create([
            'source' => 'purchased',
            'status' => 'active',
        ]);

        try {
            app(OrderService::class)->create($student, [$variant->id]);
            $this->fail('An active enrolment should prevent another order.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_repeated_checkout_reuses_pending_order(): void
    {
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create(['price_cents' => 14500]);

        $first = app(OrderService::class)->create($student, [$variant->id]);
        $second = app(OrderService::class)->create($student, [$variant->id]);

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(14500, $first->total_cents);
        $this->assertDatabaseCount('enrolments', 0);
    }

    public function test_verified_payment_applies_configured_package_expiry_once(): void
    {
        $this->travelTo(now()->startOfMinute());
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create([
            'expiry_policy' => 'duration',
            'access_duration_days' => 90,
        ]);
        $order = app(OrderService::class)->create($student, [$variant->id]);
        $event = [
            'event_id' => 'evt_direct_checkout_paid',
            'type' => 'test.paid',
            'transaction_id' => 'txn_direct_checkout_paid',
            'order_reference' => $order->reference,
            'payload' => ['verified' => true],
        ];

        app(OrderService::class)->fulfil('test', $event);
        app(OrderService::class)->fulfil('test', $event);

        $enrolment = Enrolment::where('user_id', $student->id)->firstOrFail();
        $this->assertSame(now()->addDays(90)->toDateTimeString(), $enrolment->expires_at?->toDateTimeString());
        $this->assertDatabaseCount('enrolments', 1);
        $this->assertSame('paid', Order::findOrFail($order->id)->status);
    }

    public function test_expired_entitlement_is_inactive_and_renewal_extends_the_same_enrolment(): void
    {
        $this->travelTo(now()->startOfMinute());
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create([
            'expiry_policy' => 'duration',
            'access_duration_days' => 30,
            'renewable' => true,
        ]);
        $service = app(OrderService::class);
        $firstOrder = $service->create($student, [$variant->id]);
        $service->fulfil('test', [
            'event_id' => 'evt_initial_entitlement',
            'type' => 'test.paid',
            'transaction_id' => 'txn_initial_entitlement',
            'order_reference' => $firstOrder->reference,
            'payload' => [],
        ]);
        $enrolment = Enrolment::firstOrFail();

        $this->travel(31)->days();
        $this->assertFalse($enrolment->fresh()->isActive());

        $renewalOrder = $service->create($student, [$variant->id]);
        $service->fulfil('test', [
            'event_id' => 'evt_renewed_entitlement',
            'type' => 'test.paid',
            'transaction_id' => 'txn_renewed_entitlement',
            'order_reference' => $renewalOrder->reference,
            'payload' => [],
        ]);

        $this->assertSame('renewal', $renewalOrder->kind);
        $this->assertDatabaseCount('enrolments', 1);
        $this->assertTrue($enrolment->fresh()->isActive());
        $this->assertSame(now()->addDays(30)->toDateTimeString(), $enrolment->fresh()->expires_at?->toDateTimeString());
    }

    /**
     * @param  array<string, mixed>  $courseAttributes
     * @param  array<string, mixed>  $variantAttributes
     * @return array{Course, CourseVariant}
     */
    private function assignedCourse(array $courseAttributes = [], array $variantAttributes = []): array
    {
        $course = Course::factory()->create($courseAttributes);
        $variant = CourseVariant::factory()->for($course)->create($variantAttributes);
        $course->update(['checkout_variant_id' => $variant->id]);

        return [$course->fresh(), $variant->fresh()];
    }
}
