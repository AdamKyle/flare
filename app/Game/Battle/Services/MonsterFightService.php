<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleAttackHandler;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Support\Facades\Cache;

class MonsterFightService
{
    use ResponseBuilder;

    private MonsterPlayerFight $monsterPlayerFight;

    private BattleEventHandler $battleEventHandler;

    private WeeklyBattleService $weeklyBattleService;

    public function __construct(MonsterPlayerFight $monsterPlayerFight, BattleEventHandler $battleEventHandler, WeeklyBattleService $weeklyBattleService)
    {
        $this->monsterPlayerFight = $monsterPlayerFight;
        $this->battleEventHandler = $battleEventHandler;
        $this->weeklyBattleService = $weeklyBattleService;
    }

    public function setupMonster(Character $character, array $params): array
    {

        Cache::delete('monster-fight-' . $character->id);
        Cache::delete('character-sheet-' . $character->id);

        $data = $this->monsterPlayerFight->setUpFight($character, $params);

        if (is_array($data)) {
            return $data;
        }

        $data = $data->fightSetUp();

        if ($data['health']['current_monster_health'] <= 0) {
            $this->battleEventHandler->processMonsterDeath($character->id, $data['monster']['id']);

            event(new AttackTimeOutEvent($character));

            Cache::put('monster-fight-' . $character->id, $data, 900);

            return $this->successResult($data);
        }

        if ($data['health']['current_character_health'] <= 0) {
            $monster = Monster::find($data['monster']['id']);

            $this->battleEventHandler->processDeadCharacter($character, $monster);
        }

        Cache::put('monster-fight-' . $character->id, $data, 900);

        return $this->successResult($data);
    }

    public function fightMonster(Character $character, string $attackType): array
    {
        $cache = Cache::get('monster-fight-' . $character->id);

        if (! $this->isAtMonstersLocation($character, $cache['monster']['id'])) {
            return $this->errorResult('You are too far away from the monster. Move back to it\'s location');
        }

        if (! $this->isMonsterAlreadyDefeatedThisWeek($character, $cache['monster']['id'])) {
            return $this->errorResult('You already defeated this monster. Reset is on Sundays at 3am America/Edmonton.');
        }

        if (is_null($cache)) {
            return $this->errorResult('The monster seems to have fled. Click attack again to start a new battle. You have 15 minutes from clicking attack to attack the creature.');
        }

        $this->monsterPlayerFight->setCharacter($character);

        $this->monsterPlayerFight->fightMonster(true, $attackType);

        if ($this->monsterPlayerFight->getCharacterHealth() <= 0) {

            $monster = $this->monsterPlayerFight->getMonster();

            $monster = Monster::find($monster['id']);

            $this->battleEventHandler->processDeadCharacter($character, $monster);
        }

        $monsterHealth = $this->monsterPlayerFight->getMonsterHealth();
        $characterHealth = $this->monsterPlayerFight->getCharacterHealth();

        $cache['health']['current_character_health'] = $characterHealth;
        $cache['health']['current_monster_health'] = $monsterHealth;
        $cache['messages'] = $this->monsterPlayerFight->getBattleMessages();

        if ($monsterHealth >= 0) {
            Cache::put('monster-fight-' . $character->id, $cache, 900);
        } else {
            Cache::delete('monster-fight-' . $character->id);
            BattleAttackHandler::dispatch($character->id, $this->monsterPlayerFight->getMonster()['id'])->onQueue('default_long')->delay(now()->addSeconds(2));
        }

        return $this->successResult($cache);
    }

    public function isAtMonstersLocation(Character $character, int $monsterId): bool
    {

        $monster = Monster::find($monsterId);

        if (! is_null($monster->only_for_location_type)) {

            $location = Location::where('type', $monster->only_for_location_type)->where(
                'game_map_id',
                $character->map->game_map_id
            )->where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

            if (is_null($location)) {
                return false;
            }
        }

        return true;
    }

    public function isMonsterAlreadyDefeatedThisWeek(Character $character, int $monsterId): bool
    {
        $monster = Monster::find($monsterId);

        return $this->weeklyBattleService->canFightMonster($character, $monster);
    }
}
