<?php

namespace App\Game\Exploration\Services;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Events\ExplorationStatus;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Jobs\Exploration;
use App\Game\Maps\Events\UpdateDuelAtPosition;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Facades\Cache;

class ExplorationAutomationService {

    private MonsterPlayerFight $monsterPlayerFight;

    private BattleEventHandler $battleEventHandler;

    private CharacterCacheData $characterCacheData;

    private int $timeDelay = 0;

    public function __construct(MonsterPlayerFight $monsterPlayerFight,
                                BattleEventHandler $battleEventHandler,
                                CharacterCacheData $characterCacheData) {

        $this->monsterPlayerFight = $monsterPlayerFight;
        $this->battleEventHandler = $battleEventHandler;
        $this->characterCacheData = $characterCacheData;
    }

    /**
     * @param Character $character
     * @param array $params
     */
    public function beginAutomation(Character $character, array $params) {

        $automation = CharacterAutomation::create([
            'character_id'                   => $character->id,
            'monster_id'                     => $params['selected_monster_id'],
            'type'                           => AutomationType::EXPLORING,
            'started_at'                     => now(),
            'completed_at'                   => now()->addHours($params['auto_attack_length']),
            'move_down_monster_list_every'   => $params['move_down_the_list_every'],
            'previous_level'                 => $character->level,
            'current_level'                  => $character->level,
            'attack_type'                    => $params['attack_type'],
        ]);

        $this->setTimeDelay($character);

        event(new UpdateCharacterStatus($character));

        event(new ExplorationLogUpdate($character->user->id, 'The exploration will begin in '.$this->timeDelay.' minutes. Every '.$this->timeDelay.' minutes you will encounter the enemy up to a maximum of 70 times in a single "encounter"'));

        event(new ExplorationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        event(new UpdateDuelAtPosition($character->refresh()->user));

        $this->startAutomation($character, $automation->id, $params['attack_type']);
    }

    public function stopExploration(Character $character) {
        $characterAutomation = CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->first();

        if (is_null($characterAutomation)) {
            return response()->json([
                'message' => 'Nope. You don\'t own that.'
            ], 422);
        }

        $characterAutomation->delete();

        $this->characterCacheData->deleteCharacterSheet($character);

        $character = $character->refresh();

        Cache::delete('can-character-survive-' . $character->id);

        event(new ExplorationTimeOut($character->user, 0));
        event(new ExplorationStatus($character->user, false));
        event(new UpdateCharacterStatus($character));

        event(new ExplorationLogUpdate($character->user->id, 'Exploration has been stopped at player request.'));
    }

    public function getTimeDelay(): int {
        return $this->timeDelay;
    }

    public function setTimeDelay(Character $character): void {
        $fightTimeOutSkill = $character->skills->where('baseSkill.type', SkillTypeValue::EFFECTS_BATTLE_TIMER)->first();

        $this->timeDelay = 5 - (5 * $fightTimeOutSkill->fight_time_out_mod);
    }

    protected function startAutomation(Character $character, int $automationId, string $attackType) {

        Exploration::dispatch($character, $automationId, $attackType, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onQueue('default_long');
    }
}
