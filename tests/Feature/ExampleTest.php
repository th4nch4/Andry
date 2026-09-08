<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        // "/" redirects to the sign-in screen, so boot is smoke-tested against that.
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
