<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminMonitoringChannelAuthorizationTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function testAdminCanAuthorizeExplorationMonitoringChannel(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-exploration');
        $result = $callback($admin);

        $this->assertTrue($result);
    }

    public function testNonAdminCannotAuthorizeExplorationMonitoringChannel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-exploration');
        $result = $callback($user);

        $this->assertFalse($result);
    }

    public function testAdminCanAuthorizeFactionLoyaltyMonitoringChannel(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-faction-loyalty');
        $result = $callback($admin);

        $this->assertTrue($result);
    }

    public function testNonAdminCannotAuthorizeFactionLoyaltyMonitoringChannel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-faction-loyalty');
        $result = $callback($user);

        $this->assertFalse($result);
    }

    public function testAdminCanAuthorizeDelveMonitoringChannel(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-delve');
        $result = $callback($admin);

        $this->assertTrue($result);
    }

    public function testNonAdminCannotAuthorizeDelveMonitoringChannel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-delve');
        $result = $callback($user);

        $this->assertFalse($result);
    }
}
