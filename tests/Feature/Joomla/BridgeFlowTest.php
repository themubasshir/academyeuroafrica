<?php

namespace Tests\Feature\Joomla;

use App\Models\CourseVariant;
use App\Models\ExternalIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BridgeFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.joomla.base_url', 'https://euroafricamedia.eu');
        config()->set('services.joomla.bridge_secret', 'shared-secret');
    }

    public function test_start_issues_state_and_redirects_to_joomla(): void
    {
        $response = $this->get(route('bridge.start'));

        $response->assertRedirect();
        $this->assertStringContainsString('com_eamnbridge', $response->headers->get('Location'));
        $this->assertStringContainsString('state=', $response->headers->get('Location'));
        $this->assertNotNull(session('bridge_state'));
    }

    public function test_start_ignores_unapproved_redirect_destination(): void
    {
        $this->get(route('bridge.start', ['target' => '/admin/users']))
            ->assertRedirect();

        $this->assertNull(session('url.intended'));
    }

    public function test_consume_rejects_wrong_state(): void
    {
        $this->withSession(['bridge_state' => str_repeat('a', 64)])
            ->get(route('bridge.consume', ['code' => str_repeat('b', 64), 'state' => str_repeat('c', 64)]))
            ->assertForbidden();
    }

    public function test_consume_logs_in_existing_mapped_user(): void
    {
        $user = User::factory()->student()->create(['status' => 'active']);
        ExternalIdentity::create([
            'user_id' => $user->id, 'provider' => 'joomla', 'subject' => '42', 'email' => $user->email, 'linked_at' => now(),
        ]);

        Http::fake([
            '*' => Http::response(['sub' => '42', 'email' => $user->email, 'name' => $user->name, 'groups' => []]),
        ]);

        $state = str_repeat('a', 64);
        $this->withSession([
            'bridge_state' => $state,
            'bridge_state_expires_at' => now()->addMinute()->timestamp,
        ])
            ->get(route('bridge.consume', ['code' => str_repeat('e', 64), 'state' => $state]));

        $this->assertAuthenticatedAs($user);
    }

    public function test_consume_provisions_new_student(): void
    {
        Http::fake([
            '*' => Http::response(['sub' => '77', 'email' => 'new@joomla.eu', 'name' => 'New User', 'groups' => []]),
        ]);

        $state = str_repeat('a', 64);
        $this->withSession([
            'bridge_state' => $state,
            'bridge_state_expires_at' => now()->addMinute()->timestamp,
        ])
            ->get(route('bridge.consume', ['code' => str_repeat('e', 64), 'state' => $state]));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('external_identities', ['provider' => 'joomla', 'subject' => '77']);
        $this->assertDatabaseHas('users', ['email' => 'new@joomla.eu']);
    }

    public function test_consume_refuses_admin_with_joomla_identity(): void
    {
        $user = User::factory()->academyAdmin()->create(['status' => 'active']);
        ExternalIdentity::create([
            'user_id' => $user->id, 'provider' => 'joomla', 'subject' => '42', 'email' => $user->email, 'linked_at' => now(),
        ]);
        Http::fake([
            '*' => Http::response(['sub' => '42', 'email' => $user->email, 'name' => $user->name, 'groups' => []]),
        ]);

        $state = str_repeat('a', 64);
        $this->withSession([
            'bridge_state' => $state,
            'bridge_state_expires_at' => now()->addMinute()->timestamp,
        ])
            ->get(route('bridge.consume', ['code' => str_repeat('e', 64), 'state' => $state]));

        $this->assertGuest();
    }

    public function test_email_collision_creates_conflict_not_merge(): void
    {
        $existing = User::factory()->create(['email' => 'clash@x.eu']);
        Http::fake([
            '*' => Http::response(['sub' => '99', 'email' => 'clash@x.eu', 'name' => 'Clash', 'groups' => []]),
        ]);

        $state = str_repeat('a', 64);
        $this->withSession([
            'bridge_state' => $state,
            'bridge_state_expires_at' => now()->addMinute()->timestamp,
        ])
            ->get(route('bridge.consume', ['code' => str_repeat('f', 64), 'state' => $state]));

        $this->assertGuest();
        $this->assertDatabaseHas('identity_conflicts', ['subject' => '99', 'email' => 'clash@x.eu']);
    }

    public function test_package_entry_preserves_purchasable_variant_for_unauthenticated_sso(): void
    {
        $variant = CourseVariant::factory()->create();
        DB::table('joomla_page_mappings')->insert([
            'path' => '/courses/bridge-test',
            'programme_id' => $variant->course->programme_id,
            'course_variant_id' => $variant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('bridge.package', ['path' => '/courses/bridge-test']))
            ->assertRedirect(route('bridge.start'));

        $this->assertSame($variant->id, session('bridge_intent.variant_id'));
        $this->assertSame(route('checkout.show', $variant), session('url.intended'));
    }

    public function test_package_entry_rejects_draft_variant(): void
    {
        $variant = CourseVariant::factory()->create(['status' => 'draft']);
        DB::table('joomla_page_mappings')->insert([
            'path' => '/courses/draft-test',
            'course_variant_id' => $variant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('bridge.package', ['path' => '/courses/draft-test']))->assertNotFound();
    }

    public function test_package_entry_rejects_unknown_mapping(): void
    {
        $this->get(route('bridge.package', ['path' => '/courses/unknown']))->assertNotFound();
    }

    public function test_expired_bridge_state_is_rejected(): void
    {
        $state = str_repeat('a', 64);

        $this->withSession([
            'bridge_state' => $state,
            'bridge_state_expires_at' => now()->subMinute()->timestamp,
        ])->get(route('bridge.consume', ['code' => str_repeat('b', 64), 'state' => $state]))
            ->assertForbidden();
    }

    public function test_replayed_state_is_rejected(): void
    {
        Http::fake([
            '*' => Http::response(['sub' => '88', 'email' => 'replay@joomla.eu', 'name' => 'Replay', 'groups' => []]),
        ]);
        $state = str_repeat('b', 64);
        $session = [
            'bridge_state' => $state,
            'bridge_state_expires_at' => now()->addMinute()->timestamp,
        ];

        $this->withSession($session)
            ->get(route('bridge.consume', ['code' => str_repeat('a', 64), 'state' => $state]))
            ->assertRedirect();

        $this->get(route('bridge.consume', ['code' => str_repeat('a', 64), 'state' => $state]))
            ->assertForbidden();
    }

    public function test_new_joomla_groups_do_not_elevate_student(): void
    {
        Http::fake([
            '*' => Http::response([
                'sub' => '89',
                'email' => 'groups@joomla.eu',
                'name' => 'Groups',
                'groups' => ['8', 'Super Users'],
            ]),
        ]);
        $state = str_repeat('c', 64);

        $this->withSession([
            'bridge_state' => $state,
            'bridge_state_expires_at' => now()->addMinute()->timestamp,
        ])->get(route('bridge.consume', ['code' => str_repeat('c', 64), 'state' => $state]));

        $user = User::where('email', 'groups@joomla.eu')->firstOrFail();
        $this->assertTrue($user->hasRole('student'));
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isTrainer());
    }

    public function test_package_intent_survives_identity_exchange_to_checkout(): void
    {
        $variant = CourseVariant::factory()->create();
        $state = str_repeat('e', 64);
        Http::fake([
            '*' => Http::response(['sub' => '90', 'email' => 'package@joomla.eu', 'name' => 'Package', 'groups' => []]),
        ]);

        $this->withSession([
            'bridge_state' => $state,
            'bridge_state_expires_at' => now()->addMinute()->timestamp,
            'bridge_intent' => [
                'variant_id' => $variant->id,
                'mapping_id' => 1,
                'expires_at' => now()->addMinute()->timestamp,
            ],
            'url.intended' => route('checkout.show', $variant),
        ])->get(route('bridge.consume', ['code' => str_repeat('a', 64), 'state' => $state]))
            ->assertRedirect(route('checkout.show', $variant));

        $this->assertNull(session('bridge_intent'));
    }

    public function test_events_endpoint_disables_account(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        ExternalIdentity::create([
            'user_id' => $user->id, 'provider' => 'joomla', 'subject' => '42', 'email' => $user->email, 'linked_at' => now(),
        ]);

        $body = json_encode(['sub' => '42', 'event' => 'disabled']);
        $ts = (string) time();
        $sig = hash_hmac('sha256', $ts."\n".$body, 'shared-secret');

        $this->call('POST', route('bridge.events'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_EAMN_TIMESTAMP' => $ts,
            'HTTP_X_EAMN_SIGNATURE' => $sig,
        ], $body)->assertOk();

        $this->assertSame('disabled', $user->fresh()->status);
    }
}
