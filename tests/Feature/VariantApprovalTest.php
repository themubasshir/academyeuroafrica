<?php

namespace Tests\Feature;

use App\Domain\Checkout\OrderService;
use App\Models\Course;
use App\Models\CourseVariant;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class VariantApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makeVariant(array $over = []): CourseVariant
    {
        $prog = Programme::create(['name' => 'P', 'slug' => 'p', 'status' => 'published']);
        $course = Course::create([
            'programme_id' => $prog->id, 'title' => 'C', 'slug' => 'c',
            'delivery_types' => ['live'], 'status' => 'published',
        ]);

        return CourseVariant::create(array_merge([
            'course_id' => $course->id, 'name' => 'Std', 'delivery_type' => 'live',
            'price_cents' => 9900, 'currency' => 'EUR',
            'status' => 'published', 'approval_status' => 'approved',
        ], $over));
    }

    public function test_published_but_unapproved_variant_is_not_purchasable(): void
    {
        $v = $this->makeVariant(['approval_status' => 'pending']);
        $this->assertFalse($v->isPurchasable());
    }

    public function test_approved_and_published_variant_is_purchasable(): void
    {
        $v = $this->makeVariant();
        $this->assertTrue($v->isPurchasable());
    }

    public function test_null_price_variant_is_not_purchasable_even_if_published(): void
    {
        $v = $this->makeVariant(['price_cents' => null]);
        $this->assertFalse($v->isPurchasable()); // isPurchasable also needs price guard
        $this->assertSame('Price on application', $v->priceFormatted());
    }

    public function test_order_creation_rejects_unapproved_variant(): void
    {
        $v = $this->makeVariant(['approval_status' => 'pending']);
        $user = User::factory()->create();

        $this->expectException(HttpException::class);
        (new OrderService)->create($user, [$v->id]);
    }

    public function test_price_is_read_server_side_not_from_client(): void
    {
        $v = $this->makeVariant(['price_cents' => 9900]);
        $user = User::factory()->create();
        $order = (new OrderService)->create($user, [$v->id]);

        // Total comes from the variant row, never from request input.
        $this->assertSame(9900, $order->total_cents);
        $this->assertSame(9900, $order->items->first()->unit_price_cents);
    }
}
