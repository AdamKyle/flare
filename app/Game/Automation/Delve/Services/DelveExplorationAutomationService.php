<?php

namespace App\Game\Automation\Delve\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Game\Automation\Delve\Events\DelveStatusUpdated;
use App\Game\Automation\Delve\Jobs\DelveExploration as DelveExplorationProcessing;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Values\LocationType;
use Illuminate\Support\Facades\Cache;

class DelveExplorationAutomationService
{
    use ResponseBuilder;

    private int $timeDelay = 5;

    /**
     * @param CharacterCacheData $characterCacheData The character cache data service.
     */
    public function __construct(
        private readonly CharacterCacheData $characterCacheData,
    ) {}

    /**
     * Start Delve automation for the character at the given location with the given options.
     *
     * @param Character $character The character starting Delve.
     * @param Location $location The Delve location.
     * @param array $params The Delve start options.
     * @return void
     */
    public function beginAutomation(Character $character, Location $location, array $params)
    {

        $monsterId = Monster::where('is_celestial_entity', false)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('game_map_id', $character->map->game_map_id)
            ->whereIn('only_for_location_type', [LocationType::CAVE_OF_SHADOWS->value])
            ->whereNull('raid_special_attack_type')
            ->inRandomOrder()
            ->first()
            ->id;

        $automation = CharacterAutomation::create([
            'character_id' => $character->id,
            'monster_id' => $monsterId,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => $params['attack_type'],
        ]);

        $delveExploration = DelveExploration::create([
            'character_id' => $character->id,
            'monster_id' => $monsterId,
            'started_at' => now(),
            'ended_reason' => null,
            'panel_dismissed_at' => null,
            'attack_type' => $params['attack_type'],
        ]);

        $this->setTimeDelay($location);

        event(new UpdateCharacterStatus($character));

        event(new AutomationLogUpdate($character->user->id, 'The Delve will begin in '.$location->minutes_between_delve_fights.' minutes. every three minutes you will fight '.$params['pack_size'].' enemy(ies). You will fight a new pack of or creature every 3 minutes (randomly chosen beast). A pack is always made up of the same creature.'));

        event(new AutomationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        event(new DelveStatusUpdated($character->user->id));

        $this->startAutomation($character, $location, $automation->id, $delveExploration->id, $params);
    }

    /**
     * Stop the character's active Delve automation.
     *
     * @param Character $character The character stopping Delve.
     * @return array|void
     */
    public function stopExploration(Character $character)
    {
        $characterAutomation = CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE->value)->first();

        if (is_null($characterAutomation)) {
            return $this->errorResult('Nope. You don\'t own that.');
        }

        $characterAutomation->delete();

        DelveExploration::where('character_id', $character->id)->whereNull('completed_at')->first()->update([
            'completed_at' => now(),
            'ended_reason' => 'player_stopped',
            'panel_dismissed_at' => null,
        ]);

        $this->characterCacheData->deleteCharacterSheet($character);

        $character = $character->refresh();

        Cache::delete('can-character-survive-'.$character->id);

        event(new AutomationTimeOut($character->user, 0));
        event(new AutomationStatus($character->user, false));
        event(new UpdateCharacterStatus($character));
        event(new AutomationLogUpdate($character->user->id, 'Delve has been stopped at player request.'));
        event(new DelveStatusUpdated($character->user->id));
    }

    /**
     * Set the delay, in minutes, between Delve fight rounds for the location.
     *
     * @param Location $location The Delve location.
     * @return void This method does not return a value.
     */
    public function setTimeDelay(Location $location): void
    {
        $this->timeDelay = $location->minutes_between_delve_fights;
    }

    /**
     * Dispatch the delayed Delve exploration job for the character's automation.
     *
     * @param Character $character The character delving.
     * @param Location $location The Delve location.
     * @param int $automationId The character automation id.
     * @param int $delveAutomationId The Delve exploration record id.
     * @param array $params The Delve fight parameters.
     * @return void
     */
    protected function startAutomation(Character $character, Location $location, int $automationId, int $delveAutomationId, array $params)
    {
        DelveExplorationProcessing::dispatch($character->id, $location->id, $automationId, $delveAutomationId, $params, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onConnection('long_running')->onQueue('default_long');
    }
}
