<?php

namespace App\Game\Exploration\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\Monster;
use App\Flare\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Events\ExplorationStatus;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Jobs\DelveExploration as DelveExplorationProcessing;
use Illuminate\Support\Facades\Cache;

class DelveExplorationAutomationService
{

    private int $timeDelay = 5;

    public function __construct(
        private readonly CharacterCacheData $characterCacheData
    ) {}

    public function beginAutomation(Character $character, array $params)
    {

        $monsterId = Monster::where('is_celestial_entity', false)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('game_map_id', $character->map->game_map_id)
            ->inRandomOrder()
            ->first()
            ->id;

        $automation = CharacterAutomation::create([
            'character_id' => $character->id,
            'monster_id' => $monsterId,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => $params['attack_type'],
        ]);

        $delveExploration = DelveExploration::create([
            'character_id' => $character->id,
            'monster_id' => $monsterId,
            'started_at' => now(),
            'attack_type' => $params['attack_type'],
        ]);

        $this->setTimeDelay();

        event(new UpdateCharacterStatus($character));

        event(new ExplorationLogUpdate($character->user->id, 'The Delve will being in 3 minutes. You will fight a random monster every time, 
        that monster will grow in strength until you simply cannot defeat it or you manage to survive 8 solid hours, good luck with that child. Monsters will grow by 6.25% per successful fight 
        for a max of 1000%. Enemy percentage based stats such as spell evasion, enchanting damage and so on will no grow beyond 125%. No enemy will have elemental atonements. You will fight one monster at a time, every 3 minutes.'));

        event(new ExplorationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        $this->startAutomation($character, $automation->id, $delveExploration->id, $params['attack_type']);
    }

    public function stopExploration(Character $character)
    {
        $characterAutomation = CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE)->first();

        if (is_null($characterAutomation)) {
            return response()->json([
                'message' => 'Nope. You don\'t own that.',
            ], 422);
        }

        $characterAutomation->delete();

        DelveExploration::where('character_id', $character->id)->whereNull('completed_at')->first()->update([
            'completed_at' => now(),
        ]);

        $this->characterCacheData->deleteCharacterSheet($character);

        $character = $character->refresh();

        Cache::delete('can-character-survive-' . $character->id);

        event(new ExplorationTimeOut($character->user, 0));
        event(new ExplorationStatus($character->user, false));
        event(new UpdateCharacterStatus($character));
        event(new ExplorationLogUpdate($character->user->id, 'Delve has been stopped at player request.'));
    }

    public function setTimeDelay(): void
    {
        $this->timeDelay = 3;
    }

    protected function startAutomation(Character $character, int $automationId, int $delveAutomationId, string $attackType)
    {
        DelveExplorationProcessing::dispatch($character, $automationId, $delveAutomationId, $attackType, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onQueue('default_long');
    }
}
