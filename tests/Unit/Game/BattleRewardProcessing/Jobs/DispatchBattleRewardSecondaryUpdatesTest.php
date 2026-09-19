<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Game\BattleRewardProcessing\Jobs\DispatchBattleRewardSecondaryUpdates;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class DispatchBattleRewardSecondaryUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatching_secondary_updates_emits_tops_and_compatibility_currency_events(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        DispatchBattleRewardSecondaryUpdates::dispatch($character->id)
            ->onConnection('battle_reward_processing')
            ->onQueue('battle_reward_secondary');

        Event::assertDispatched(UpdateTopBarEvent::class);
        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function test_missing_character_secondary_update_finishes_without_dispatching_player_events(): void
    {
        Event::fake();

        DispatchBattleRewardSecondaryUpdates::dispatch(999999999)
            ->onConnection('battle_reward_processing')
            ->onQueue('battle_reward_secondary');

        Event::assertNotDispatched(UpdateTopBarEvent::class);
        Event::assertNotDispatched(UpdateCharacterCurrenciesEvent::class);
    }
}
