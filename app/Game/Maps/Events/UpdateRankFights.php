<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Map;
use App\Flare\Models\RankFight;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Game\Core\Traits\KingdomCache;
Use App\Flare\Models\User;

class UpdateRankFights implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var bool $showRankSelection
     */
    public bool $showRankSelection = false;

    /**
     * @var int $ranks
     */
    public int $ranks;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param bool $showRankSelection
     */
    public function __construct(User $user, bool $showRankSelection) {
        $rank                    = RankFight::first();

        $this->showRankSelection = $showRankSelection;
        $this->ranks             = is_null($rank) ? 1 : $rank->current_rank;
        $this->user              = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-rank-fight-' . $this->user->id);
    }
}
