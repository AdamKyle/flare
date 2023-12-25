<?php

namespace Tests\Console\Raids;

use App\Flare\Models\RaidBossParticipation;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;

class ResetDailyRaidAttackLimitsTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateItemAffix, CreateMonster, CreateItem, CreateLocation, CreateRaid, CreateEvent;

    public function testResetRaidFight() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster();
        $item = $this->createItem();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id'                   => $monster->id,
            'raid_monster_ids'               => [$monster->id],
            'raid_boss_location_id'          => $location->id,
            'corrupted_location_ids'         => [$location->id],
            'artifact_item_id'               => $item->id,
        ]);

        $this->createEvent([
            'raid_id' => $raid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now(),
        ]);

        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'attacks_left' => 0,
            'damage_dealt' => 1000000,
            'killed_boss' => true,
        ]);

        Artisan::call('reset:daily-raid-attack-limits');

        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testDoNotResetRaidFightWhenNoEvent() {
        Event::fake();

        Artisan::call('reset:daily-raid-attack-limits');

        Event::assertNotDispatched(GlobalMessageEvent::class);
    }

    public function testDoNotresetRaidFightWhenRaidBossNotDead() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster();
        $item = $this->createItem();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id'                   => $monster->id,
            'raid_monster_ids'               => [$monster->id],
            'raid_boss_location_id'          => $location->id,
            'corrupted_location_ids'         => [$location->id],
            'artifact_item_id'               => $item->id,
        ]);

        $this->createEvent([
            'raid_id' => $raid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now(),
        ]);

        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'attacks_left' => 0,
            'damage_dealt' => 1000000,
            'killed_boss' => false,
        ]);

        Artisan::call('reset:daily-raid-attack-limits');

        Event::assertNotDispatched(GlobalMessageEvent::class);
    }
}
