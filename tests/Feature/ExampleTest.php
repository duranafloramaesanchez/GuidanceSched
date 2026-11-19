<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_is_sent_to_login_from_the_home_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
