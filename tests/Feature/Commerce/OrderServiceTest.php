<?php

namespace Tests\Feature\Commerce;

use App\Domain\Checkout\OrderService;
use App\Models\CourseVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_created_with_server_side_price(): void
    {
        $user = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create(['price_cents' => 12345]);

        $order = (new OrderService)->create($user, [$variant->id]);

        $this->assertSame('pending', $order->status);
        $this->assertSame(12345, $order->total_cents);
        $this->assertCount(1, $order->items);
        $this->assertSame(12345, $order->items->first()->unit_price_cents);
    }

    public function test_draft_variant_is_not_purchasable(): void
    {
        $user = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create(['status' => 'draft']);

        $this->expectException(HttpException::class);
        (new OrderService)->create($user, [$variant->id]);
    }
}
