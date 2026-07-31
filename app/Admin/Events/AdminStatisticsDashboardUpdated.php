<?php

namespace App\Admin\Events;

use App\Admin\Services\AdminStatisticsDashboardService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminStatisticsDashboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $snapshot;

    public function __construct(?array $snapshot = null)
    {
        $this->snapshot = $snapshot ?? resolve(AdminStatisticsDashboardService::class)->snapshot();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin-statistics-dashboard');
    }

    public function broadcastAs(): string
    {
        return 'admin.statistics.dashboard.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'snapshot' => $this->snapshot,
        ];
    }
}
