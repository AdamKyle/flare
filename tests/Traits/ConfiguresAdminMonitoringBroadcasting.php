<?php

namespace Tests\Traits;

trait ConfiguresAdminMonitoringBroadcasting
{
    /**
     * Configure the Reverb testing broadcaster and register the production admin
     * monitoring channel authorization rules for the `/broadcasting/auth` endpoint.
     */
    public function configureAdminMonitoringBroadcasting(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-reverb-key',
            'broadcasting.connections.reverb.secret' => 'test-reverb-secret',
            'broadcasting.connections.reverb.app_id' => 'test-reverb-app-id',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 8080,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);

        require base_path('routes/admin/monitoring/channels.php');
    }
}
