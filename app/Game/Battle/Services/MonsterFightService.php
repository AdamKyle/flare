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
use Psr\SimpleCache\InvalidArgumentException;

class MonsterFightService
{
    use ResponseBuilder;

    /**
     * @param MonsterPlayerFight $monsterPlayerFight
     * @param BattleEventHandler $battleEventHandler
     * @param WeeklyBattleService $weeklyBattleService
     */
    public function __construct(private readonly MonsterPlayerFight $monsterPlayerFight, private readonly BattleEventHandler $battleEventHandler, private readonly WeeklyBattleService $weeklyBattleService)
    {}

    /**
     * @param Character $character
     * @param array $params
     * @param bool $returnData
     * @param bool $isDelve
     * @return array
     * @throws InvalidArgumentException
     */
    public function setupMonster(Character $character, array $params, bool $returnData = false, bool $isDelve = false): array
    {
        Cache::delete('monster-fight-' . $character->id);
        Cache::delete('character-sheet-' . $character->id);

        $params = $this->fetchPossibleDelveMonsterId($character, $params, $isDelve);

        $data = $this->monsterPlayerFight->setUpFight($character, $params, $isDelve);

        if (is_array($data)) {
            return $data;
        }

        $data = $data->fightSetUp();

        if ($data['health']['current_monster_health'] <= 0) {

            if (!$returnData) {
                $this->battleEventHandler->processMonsterDeath($character->id, $data['monster']['id']);
            }

            event(new AttackTimeOutEvent($character));

            Cache::put('monster-fight-' . $character->id, $data, 900);

            if ($returnData) {
                return $data;
            }

            return $this->successResult($data);
        }

        if ($data['health']['current_character_health'] <= 0) {
            $monster = Monster::find($data['monster']['id']);

            $this->battleEventHandler->processDeadCharacter($character, $monster);
        }

        Cache::put('monster-fight-' . $character->id, $data, 900);

        if ($returnData) {
            return $data;
        }

        return $this->successResult($data);
    }

    /**
     * @return Monster|null
     */
    public function getMonster(): ?Monster {

        if (empty($this->monsterPlayerFight->getMonster())) {
            return null;
        }

        return Monster::find($this->monsterPlayerFight->getMonster()['id']);
    }

    /**
     * @param Character $character
     * @param string $attackType
     * @param bool $onlyOnce
     * @param bool $returnData
     * @return array
     * @throws InvalidArgumentException
     */
    public function fightMonster(Character $character, string $attackType, bool $onlyOnce = true, bool $returnData = false): array
    {
        $cache = Cache::get('monster-fight-' . $character->id);

        if (is_null($cache)) {

            if ($returnData) {
                return [];
            }

            return $this->errorResult('The monster seems to have fled. Click attack again to start a new battle. You have 15 minutes from clicking attack to attack the creature.');
        }

        if (!isset($cache['monster']) || !is_array($cache['monster']) || !isset($cache['monster']['id'])) {

            if ($returnData) {
                return [];
            }

            return $this->errorResult('The monster seems to have fled. Click attack again to start a new battle. You have 15 minutes from clicking attack to attack the creature.');
        }

        $monsterId = $cache['monster']['id'];

        if (! $this->isAtMonstersLocation($character, $monsterId)) {
            if ($returnData) {
                return [];
            }

            return $this->errorResult('You are too far away from the monster. Move back to it\'s location');
        }

        if (! $this->isMonsterAlreadyDefeatedThisWeek($character, $monsterId)) {
            if ($returnData) {
                return [];
            }

            return $this->errorResult('You already defeated this monster. Reset is on Sundays at 3am America/Edmonton.');
        }

        $this->monsterPlayerFight->setCharacter($character);

        $this->monsterPlayerFight->fightMonster($onlyOnce, $attackType);

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
        }

        if ($returnData) {
            return $cache;
        }

        Cache::delete('monster-fight-' . $character->id);
        BattleAttackHandler::dispatch($character->id, $this->monsterPlayerFight->getMonster()['id'])->onQueue('default_long')->delay(now()->addSeconds(2));

        return $this->successResult($cache);
    }

    /**
     * @param Character $character
     * @param int $monsterId
     * @return bool
     */
    public function isAtMonstersLocation(Character $character, int $monsterId): bool
    {
        $monster = Monster::find($monsterId);

        if (is_null($monster)) {
            return false;
        }

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

    /**
     * @param Character $character
     * @param int $monsterId
     * @return bool
     */
    public function isMonsterAlreadyDefeatedThisWeek(Character $character, int $monsterId): bool
    {
        $monster = Monster::find($monsterId);

        if (is_null($monster)) {
            return false;
        }

        return $this->weeklyBattleService->canFightMonster($character, $monster);
    }

    private function fetchPossibleDelveMonsterId(Character $character, array $params, bool $isDelve): array {

        if (!$isDelve) {
            return $params;
        }

        $selectedMonsterId = $params['selected_monster_id'];
        $packSize          = $params['pack_size'] ?? 0;

        $cachedMonsterForDelve = Cache::get('delve-monster-' . $character->id . '-' . $selectedMonsterId . '-fight');

        if (!is_null($cachedMonsterForDelve) && $packSize > 1) {

            $params['cached_monster'] = $cachedMonsterForDelve;

            return $params;
        }

        $monsterId = Monster::query()
            ->where('game_map_id', $character->map->game_map_id)
            ->inRandomOrder()
            ->value('id');

        return array_merge($params, ['selected_monster_id' => $monsterId]);
    }
}
