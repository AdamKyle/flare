<?php

namespace App\Game\Kingdoms\Events;

use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCapitalCityBuildingQueueRequest implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private int $userId;

    public bool $isLoading;

    public string $message;

    public string $type;

    public ?int $processedKingdomId;

    public ?string $processedKingdomName;

    public ?string $requestType;

    public ?array $queueData;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, bool $isLoading, string $message, string $type, ?int $processedKingdomId = null, ?string $processedKingdomName = null, ?string $requestType = null, ?array $queueData = null)
    {

        $this->userId = $userId;
        $this->isLoading = $isLoading;
        $this->message = $message;
        $this->type = $type;
        $this->processedKingdomId = $processedKingdomId;
        $this->processedKingdomName = $processedKingdomName;
        $this->requestType = $requestType;
        $this->queueData = $queueData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('capital-city-building-queue-request-'.$this->userId);
    }

    public function broadcastWith(): array
    {
        return [
            'isLoading' => $this->isLoading,
            'message' => $this->message,
            'type' => $this->type,
            'processed_kingdom_id' => $this->processedKingdomId,
            'processed_kingdom_name' => $this->processedKingdomName,
            'request_type' => $this->requestType,
            'queue_data' => $this->queueData,
        ];
    }
}
