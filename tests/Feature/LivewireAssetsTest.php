<?php

namespace Tests\Feature;

use Tests\TestCase;

class LivewireAssetsTest extends TestCase
{
    /**
     * Regression guard: interactivity was dead in the browser because the layout
     * never emitted the Livewire runtime script tag (only the config blob).
     */
    public function test_login_page_loads_livewire_runtime_script(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/<script[^>]+src="[^"]*livewire[^"]*\.js/',
            $response->getContent(),
            'Livewire runtime script tag missing from rendered layout.'
        );
    }

    /**
     * Regression guard: the strict CSP must not block Livewire/Alpine. The app
     * serves the CSP-safe build, so eval-requiring `new Function` is avoided.
     */
    public function test_csp_header_is_present_and_livewire_safe(): void
    {
        $response = $this->get('/login');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertTrue(config('livewire.csp_safe'), 'CSP-safe Livewire build must stay enabled: CSP omits unsafe-eval.');
    }
}
