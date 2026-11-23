<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Jobs\BattleAttackHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class BattleAttackHandlerTest extends TestCase
{
    use CreateMonster, RefreshDatabase;

    public function testManualMonsterDeathRewardIsQueuedAtFirstPriority(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster();

        (new BattleAttackHandler($character->id, $monster->id))->handle(resolve(BattleEventHandler::class));

        $request = CharacterBattleRewardRequest::where('character_id', $character->id)->first();

        $this->assertSame(BattleRewardRequestPriority::FIRST, $request->priority);
    }
}
