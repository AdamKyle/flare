<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCapitalCityUnitRecruitments implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public array $kingdomUnitRecruitment;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character, Kingdom $kingdom)
    {

        $kingdomUnitRecruitment = resolve(CapitalCityManagementService::class)
            ->fetchKingdomsForSelection($kingdom, true);

        $this->user = $character->user;
        $this->kingdomUnitRecruitment = $kingdomUnitRecruitment;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('capital-city-update-kingdom-unit-data-' . $this->user->id);
    }
}
