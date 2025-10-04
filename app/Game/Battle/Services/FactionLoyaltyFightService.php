<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Npc;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;

class FactionLoyaltyFightService
{
    use ResponseBuilder;

    public function __construct(private readonly MonsterPlayerFight $monsterPlayerFight, private readonly BattleEventHandler $battleEventHandler, private readonly FactionLoyaltyService $factionLoyaltyService) {}

    public function fightMonster(Character $character, int $monsterId, int $npcId, string $attackType): array
    {

        $npc = Npc::find($npcId);

        $params = [
            'selected_monster_id' => $monsterId,
            'attack_type' => $attackType,
        ];

        event(new AttackTimeOutEvent($character));

        $result = $this->monsterPlayerFight->setUpFight($character, $params)->fightMonster(true);

        if ($result) {
            $this->battleEventHandler->processMonsterDeath($character->id, $monsterId);

            return $this->successResult([
                'message' => 'You have slaughtered the monster! '.$npc->real_name.' is happy with your progress! (Check Server Messages for more details on items and additional messages. On mobile, you can select Server Messages from the Orange Chat Tabs drop down below)',
                'must_revive' => false,
            ]);
        }

        if (! $result) {
            $this->battleEventHandler->processDeadCharacter($character);

            return $this->successResult([
                'message' => 'You have fallen in battle. You must revive! Aside from that, '.$npc->real_name.' is ashamed to know you. (Go and level your character up through exploration or manual fighting or change your gear up. These fights reset, so you will just keep dying. Try fighting it manually to understand why.)',
                'must_revive' => true,
            ]);
        }

        return $this->successResult([
            'message' => $npc->real_name.' is annoyed you cannot kill the requested creature in one hit. "Child I grow bored of these games." (You might be to weak or your equipment is not good enough. Have you invested in Class Skills?) Try fighting it manually to understand why you didnt kill it one hit. (You can also manually fight the creature until its dead to gain the point.)',
            'must_revive' => true,
        ]);
    }
}
