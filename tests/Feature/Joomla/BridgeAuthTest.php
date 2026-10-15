<?php

namespace Tests\Feature\Joomla;

use App\Services\Joomla\BridgeClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BridgeAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.joomla.base_url', 'https://euroafricamedia.eu');
        config()->set('services.joomla.bridge_secret', 'test-secret');
    }

    public function test_exchange_code_returns_claims(): void
    {
        Http::fake([
            'euroafricamedia.eu/*' => Http::response([
                'sub' => '42', 'email' => 'a@b.c', 'name' => 'A B', 'groups' => [],
            ]),
        ]);

        $claims = (new BridgeClient)->exchangeCode(str_repeat('a', 64));

        $this->assertSame('42', $claims['sub']);
        $this->assertSame('a@b.c', $claims['email']);
    }

    public function test_exchange_request_is_hmac_signed(): void
    {
        Http::fake([
            '*' => function ($request) {
                $this->assertNotEmpty($request->header('X-EAMN-Signature'));
                $this->assertNotEmpty($request->header('X-EAMN-Timestamp'));
                return Http::response(['sub' => '1', 'email' => 'e@e.e', 'name' => 'N']);
            },
        ]);

        (new BridgeClient)->exchangeCode(str_repeat('b', 64));
    }

    public function test_failed_exchange_throws(): void
    {
        Http::fake(['*' => Http::response('no', 401)]);

        $this->expectException(\RuntimeException::class);
        (new BridgeClient)->exchangeCode(str_repeat('c', 64));
    }
}
