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

    public function test_admin_can_authorize_exploration_monitoring_channel(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-exploration');
        $result = $callback($admin);

        $this->assertTrue($result);
    }

    public function test_non_admin_cannot_authorize_exploration_monitoring_channel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-exploration');
        $result = $callback($user);

        $this->assertFalse($result);
    }

    public function test_admin_can_authorize_faction_loyalty_monitoring_channel(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-faction-loyalty');
        $result = $callback($admin);

        $this->assertTrue($result);
    }

    public function test_non_admin_cannot_authorize_faction_loyalty_monitoring_channel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-faction-loyalty');
        $result = $callback($user);

        $this->assertFalse($result);
    }

    public function test_admin_can_authorize_delve_monitoring_channel(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-delve');
        $result = $callback($admin);

        $this->assertTrue($result);
    }

    public function test_non_admin_cannot_authorize_delve_monitoring_channel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-delve');
        $result = $callback($user);

        $this->assertFalse($result);
    }
}
