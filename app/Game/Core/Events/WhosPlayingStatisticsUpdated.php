<?php

namespace App\Game\Core\Events;

use App\Game\Core\Services\WhosPlayingStatisticsService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhosPlayingStatisticsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $snapshot;

    public function __construct(?array $snapshot = null)
    {
        $this->snapshot = $snapshot ?? resolve(WhosPlayingStatisticsService::class)->snapshot();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('whos-playing-statistics');
    }

    public function broadcastAs(): string
    {
        return 'whos.playing.statistics.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'snapshot' => $this->snapshot,
        ];
    }
}
