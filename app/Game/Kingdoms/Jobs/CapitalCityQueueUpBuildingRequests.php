<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueRequest;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CapitalCityQueueUpBuildingRequests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected readonly int $characterId, protected readonly int $kingdomId, protected readonly array $requestData, protected readonly string $requestType) {}

    public function handle(CapitalCityManagementService $capitalCityManagementService,): void
    {
        $character = Character::find($this->characterId);
        $kingdom = Kingdom::find($this->kingdomId);

        if (is_null($character)) {
            return;
        }

        if (is_null($kingdom)) {
            event(new UpdateCapitalCityBuildingQueueRequest($character->user_id, false, "Something went wrong, the capital city kingdom doesn't seem to exist", 'error'));

        }

        event(new UpdateCapitalCityBuildingQueueRequest($character->user_id, true, 'Sending off the requests. Please wait.', 'info'));

        $result = $capitalCityManagementService->sendoffBuildingRequests($character, $kingdom, $this->requestData, $this->requestType);

        event(new UpdateCapitalCityBuildingQueueRequest($character->user_id, false, $result['message'], 'success'));
    }
}
