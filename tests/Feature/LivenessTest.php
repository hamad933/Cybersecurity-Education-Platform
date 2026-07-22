<?php

namespace Tests\Feature;

use Tests\TestCase;

class LivenessTest extends TestCase
{
    public function test_liveness_is_minimal_and_non_sensitive(): void
    {
        $response = $this->getJson('/health/live')->assertOk()->assertExactJson(['status' => 'ok']);
        $body = $response->getContent();
        foreach (['APP_KEY', 'DB_PASSWORD', 'environment', 'database', 'owner'] as $sensitive) {
            $this->assertStringNotContainsString($sensitive, $body);
        }
    }
}
