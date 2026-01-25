<?php

namespace App\Game\Exploration\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DwelveExploration;
use App\Flare\Models\Monster;
use App\Flare\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Events\ExplorationStatus;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Jobs\DwelveExploration as DwelveExplorationProcessing;
use Illuminate\Support\Facades\Cache;

class DwelveExplorationAutomationService
{

    private int $timeDelay = 5;

    public function __construct(
        private readonly CharacterCacheData $characterCacheData
    ) {}

    public function beginAutomation(Character $character, array $params)
    {

        $monsterId = Monster::where('game_map_id', $character->map->game_map_id)->inRandomOrder()->first()->id;

        $automation = CharacterAutomation::create([
            'character_id' => $character->id,
            'monster_id' => $monsterId,
            'type' => AutomationType::DWELVE,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => $params['attack_type'],
        ]);

        $dwelveExploration = DwelveExploration::create([
            'character_id' => $character->id,
            'monster_id' => $monsterId,
            'started_at' => now(),
            'attack_type' => $params['attack_type'],
        ]);

        $this->setTimeDelay();

        event(new UpdateCharacterStatus($character));

        event(new ExplorationLogUpdate($character->user->id, 'The Dwelve will being in 3 minutes. You will fight a random monster every time, 
        that monster will grow in strength until you simply cannot defeat it or you manage to survive 8 solid hours, good luck with that child. Monsters will grow by 5% per successful fight.'));

        event(new ExplorationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        $this->startAutomation($character, $automation->id, $dwelveExploration->id, $params['attack_type']);
    }

    public function stopExploration(Character $character)
    {
        $characterAutomation = CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DWELVE)->first();

        if (is_null($characterAutomation)) {
            return response()->json([
                'message' => 'Nope. You don\'t own that.',
            ], 422);
        }

        $characterAutomation->delete();

        DwelveExploration::where('character_id', $character->id)->whereNull('completed_at')->first()->update([
            'completed_at' => now(),
        ]);

        $this->characterCacheData->deleteCharacterSheet($character);

        $character = $character->refresh();

        Cache::delete('can-character-survive-' . $character->id);

        event(new ExplorationTimeOut($character->user, 0));
        event(new ExplorationStatus($character->user, false));
        event(new UpdateCharacterStatus($character));
        event(new ExplorationLogUpdate($character->user->id, 'Dwelve has been stopped at player request.'));
    }

    public function setTimeDelay(): void
    {
        $this->timeDelay = 3;
    }

    protected function startAutomation(Character $character, int $automationId, int $dwelveAutomationId, string $attackType)
    {
        DwelveExplorationProcessing::dispatch($character, $automationId, $dwelveAutomationId, $attackType, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onQueue('default_long');
    }
}
