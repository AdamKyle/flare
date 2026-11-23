<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Services\FactionLoyaltyFightService;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class FactionLoyaltyFightServiceTest extends TestCase
{
    use CreateMonster, CreateNpc, RefreshDatabase;

    public function testKillingTheMonsterQueuesTheRewardAtFirstPriority(): void
    {
        Queue::fake();
        Event::fake([AttackTimeOutEvent::class]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster();
        $npc = $this->createNpc();

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn([
            'health' => ['current_character_health' => 100, 'current_monster_health' => 100],
        ]);
        $monsterFightService->shouldReceive('fightMonster')->andReturn([
            'health' => ['current_character_health' => 100, 'current_monster_health' => 0],
            'monster' => ['id' => $monster->id],
        ]);

        $this->instance(MonsterFightService::class, $monsterFightService);

        resolve(FactionLoyaltyFightService::class)->fightMonster($character, $monster->id, $npc->id, 'attack');

        $request = CharacterBattleRewardRequest::where('character_id', $character->id)->first();

        $this->assertSame(BattleRewardRequestPriority::FIRST, $request->priority);
    }
}
