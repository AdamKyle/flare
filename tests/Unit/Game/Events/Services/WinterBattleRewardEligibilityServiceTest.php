<?php

namespace Tests\Unit\Game\Events\Services;

use App\Game\Events\Services\WinterBattleRewardEligibilityService;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateScheduledEvent;

class WinterBattleRewardEligibilityServiceTest extends TestCase
{
    use CreateGameMap, CreateScheduledEvent, RefreshDatabase;

    public function test_is_eligible_when_winter_event_is_running_and_character_is_on_the_ice_plane(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'currently_running' => true,
        ]);
        $iceMap = $this->createGameMap(['name' => 'The Ice Plane']);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $iceMap)->getCharacter();

        $eligible = resolve(WinterBattleRewardEligibilityService::class)->isEligible($character->id);

        $this->assertTrue($eligible);
    }

    public function test_is_not_eligible_when_no_winter_event_is_running(): void
    {
        $iceMap = $this->createGameMap(['name' => 'The Ice Plane']);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $iceMap)->getCharacter();

        $eligible = resolve(WinterBattleRewardEligibilityService::class)->isEligible($character->id);

        $this->assertFalse($eligible);
    }

    public function test_is_not_eligible_when_character_is_off_the_ice_plane(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'currently_running' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $eligible = resolve(WinterBattleRewardEligibilityService::class)->isEligible($character->id);

        $this->assertFalse($eligible);
    }
}
