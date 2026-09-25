<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Services\BattleRewardLiveUpdateService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BattleRewardLiveUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_publishes_the_exact_live_reward_fields_including_kingdom_gold_bars(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom(['gold_bars' => 12])
            ->getCharacter();
        $character->update([
            'level' => 5,
            'xp' => 100,
            'xp_next' => 500,
            'gold' => 1000,
            'gold_dust' => 20,
            'shards' => 3,
            'copper_coins' => 7,
        ]);

        (new BattleRewardLiveUpdateService)->broadcast($character->id);

        Event::assertDispatched(UpdateBaseCharacterInformation::class, function (UpdateBaseCharacterInformation $event) {
            return $event->character === [
                'level' => 5,
                'xp' => 100,
                'xp_next' => 500,
                'gold' => 1000,
                'gold_dust' => 20,
                'shards' => 3,
                'copper_coins' => 7,
                'gold_bars' => 12,
            ];
        });
    }

    public function test_broadcast_does_nothing_for_a_missing_character(): void
    {
        Event::fake();

        (new BattleRewardLiveUpdateService)->broadcast(999999999);

        Event::assertNotDispatched(UpdateBaseCharacterInformation::class);
    }
}
