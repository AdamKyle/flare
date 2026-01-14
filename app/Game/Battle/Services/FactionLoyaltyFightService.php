<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Npc;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use Psr\SimpleCache\InvalidArgumentException;

class FactionLoyaltyFightService
{
    use ResponseBuilder;

    public function __construct(private readonly MonsterFightService $monsterFightService, private readonly BattleEventHandler $battleEventHandler, private readonly FactionLoyaltyService $factionLoyaltyService) {}

    /**
     * Fight the monster.
     *
     * - Players must kill the mnster in one hit or it doesnt count.
     *
     * @param Character $character
     * @param int $monsterId
     * @param int $npcId
     * @param string $attackType
     * @return array
     * @throws InvalidArgumentException
     */
    public function fightMonster(Character $character, int $monsterId, int $npcId, string $attackType): array
    {

        $npc = Npc::find($npcId);

        $params = [
            'selected_monster_id' => $monsterId,
            'attack_type' => $attackType,
        ];

        event(new AttackTimeOutEvent($character));

        $data = $this->monsterFightService->setupMonster($character, $params, true);

        $result = $this->handleDiedToTheMonster($character, $npc, $data);

        if (!empty($result)) {
            return $result;
        }

        $data = $this->monsterFightService->fightMonster($character, $attackType, true, true);

        $result = $this->handleDiedToTheMonster($character, $npc, $data);

        if (!empty($result)) {
            return $result;
        }

        $result = $this->handleMonsterDeath($character, $npc, $data);

        if (!empty($result)) {
            return $result;
        }

        return $this->successResult([
            'message' => $npc->real_name . ' is annoyed you cannot kill the requested creature in one hit. "Child I grow bored of these games." (You might be to weak or your equipment is not good enough. Have you invested in Class Skills?) Try fighting it manually to understand why you didnt kill it one hit. (You can also manually fight the creature until its dead to gain the point.)',
            'must_revive' => false,
        ]);
    }

    /**
     * Handle when the character dies to the monster.
     *
     * @param Character $character
     * @param Npc $npc
     * @param array $data
     * @return array
     */
    private function handleDiedToTheMonster(Character $character, Npc $npc, array $data): array
    {
        if ($data['health']['current_character_health'] <= 0) {
            $this->battleEventHandler->processDeadCharacter($character);

            return $this->successResult([
                'message' => 'You have fallen in battle. You must revive! Aside from that, ' . $npc->real_name . ' is ashamed to know you. (Go and level your character up through exploration or manual fighting or change your gear up. These fights reset, so you will just keep dying. Try fighting it manually to understand why.)',
                'must_revive' => true,
            ]);
        }

        return [];
    }

    /**
     * Handle when the monster dies.
     *
     * @param Character $character
     * @param Npc $npc
     * @param array $data
     * @return array
     */
    private function handleMonsterDeath(Character $character, Npc $npc, array $data): array
    {
        if ($data['health']['current_monster_health'] <= 0) {
            $this->battleEventHandler->processMonsterDeath($character->id, $data['monster']['id']);

            return $this->successResult([
                'message' => 'You have slaughtered the monster! ' . $npc->real_name . ' is happy with your progress! (Check Server Messages for more details on items and additional messages. On mobile, you can select Server Messages from the Orange Chat Tabs drop down below)',
                'must_revive' => false,
            ]);
        }

        return [];
    }
}
