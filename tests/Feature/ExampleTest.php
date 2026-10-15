<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        // `/` is a private-portal entry: guests are routed to login.
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }
}
