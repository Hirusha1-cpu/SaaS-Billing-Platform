<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        // Get homepage
        $response = $this->get('/');

        // Check if it returns 200
        $response->assertStatus(200);
    }
}