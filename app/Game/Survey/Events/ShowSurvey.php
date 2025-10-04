<?php

namespace App\Game\Survey\Events;

use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShowSurvey implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public bool $showSurvey;

    public ?int $surveyId = null;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, ?int $surveyId = null)
    {
        $this->user = $user;
        $this->surveyId = $surveyId;
        $this->showSurvey = ! is_null($surveyId);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('show-survey-'.$this->user->id);
    }
}
