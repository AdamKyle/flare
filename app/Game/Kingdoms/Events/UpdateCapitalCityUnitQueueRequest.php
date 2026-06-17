<?php

namespace App\Game\Kingdoms\Events;

use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCapitalCityUnitQueueRequest implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private int $userId;

    public bool $isLoading;

    public string $message;

    public string $type;

    public ?int $processedKingdomId;

    public ?string $processedKingdomName;

    public ?array $queueData;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, bool $isLoading, string $message, string $type, ?int $processedKingdomId = null, ?string $processedKingdomName = null, ?array $queueData = null)
    {

        $this->userId = $userId;
        $this->isLoading = $isLoading;
        $this->message = $message;
        $this->type = $type;
        $this->processedKingdomId = $processedKingdomId;
        $this->processedKingdomName = $processedKingdomName;
        $this->queueData = $queueData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('capital-city-unit-queue-request-'.$this->userId);
    }

    public function broadcastWith(): array
    {
        return [
            'isLoading' => $this->isLoading,
            'message' => $this->message,
            'type' => $this->type,
            'processed_kingdom_id' => $this->processedKingdomId,
            'processed_kingdom_name' => $this->processedKingdomName,
            'queue_data' => $this->queueData,
        ];
    }
}
