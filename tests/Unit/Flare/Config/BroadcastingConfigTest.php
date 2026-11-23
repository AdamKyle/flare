<?php

namespace Tests\Unit\Flare\Config;

use Tests\TestCase;

class BroadcastingConfigTest extends TestCase
{
    public function testPusherConnectionHasRequestTimeouts(): void
    {
        $this->assertSame(['connect_timeout' => 3, 'timeout' => 10], config('broadcasting.connections.pusher.client_options'));
    }
}
