<?php

namespace Tests\Feature\Commerce;

use App\Domain\Checkout\OrderService;
use App\Models\Enrolment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function paidEvent(string $ref, string $eventId = 'evt_1'): array
    {
        return [
            'event_id' => $eventId,
            'type' => 'test.paid',
            'transaction_id' => 'test_txn_'.$ref,
            'order_reference' => $ref,
            'payload' => ['stub' => true],
        ];
    }

    private function makeOrder(User $user): Order
    {
        $variant = \App\Models\CourseVariant::factory()->create(['price_cents' => 9900]);
        return (new OrderService)->create($user, [$variant->id]);
    }

    public function test_webhook_marks_order_paid_and_enrols(): void
    {
        $user = User::factory()->student()->create();
        $order = $this->makeOrder($user);

        (new OrderService)->fulfil('test', $this->paidEvent($order->reference));

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, Enrolment::where('user_id', $user->id)->count());
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $user = User::factory()->student()->create();
        $order = $this->makeOrder($user);
        $event = $this->paidEvent($order->reference);

        (new OrderService)->fulfil('test', $event);
        (new OrderService)->fulfil('test', $event); // replay

        $this->assertSame(1, Enrolment::where('user_id', $user->id)->count());
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_reaching_success_url_does_not_grant_access(): void
    {
        $user = User::factory()->student()->create();
        $order = $this->makeOrder($user);

        $this->actingAs($user)->get(route('checkout.success', ['order' => $order->reference]))
            ->assertOk();

        $this->assertSame(0, Enrolment::where('user_id', $user->id)->count());
        $this->assertSame('pending', $order->fresh()->status);
    }
}
