<?php

namespace Tests\Console\Raids;

use App\Flare\Models\RaidBossParticipation;
use App\Flare\Models\RaidBoss;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
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
    use CreateEvent, CreateItem, CreateItem, CreateItemAffix, CreateLocation, CreateMonster, CreateRaid, RefreshDatabase;

    public function testResetRaidFight()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster();
        $item = $this->createItem();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
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

        $this->assertSame(5, RaidBossParticipation::where('character_id', $character->id)
            ->where('raid_id', $raid->id)
            ->first()
            ->attacks_left);
        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testResetsTwoBossParticipationsForTheSameCharacterIndependently(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $firstMonster = $this->createMonster();
        $secondMonster = $this->createMonster();
        $item = $this->createItem();
        $firstLocation = $this->createLocation();
        $firstRaid = $this->createRaid([
            'raid_boss_id' => $firstMonster->id,
            'raid_monster_ids' => [$firstMonster->id],
            'raid_boss_location_id' => $firstLocation->id,
            'corrupted_location_ids' => [$firstLocation->id],
            'artifact_item_id' => $item->id,
        ]);
        $firstRaidBoss = RaidBoss::create([
            'raid_id' => $firstRaid->id,
            'raid_boss_id' => $firstMonster->id,
        ]);
        $secondRaidBoss = RaidBoss::create([
            'raid_id' => $firstRaid->id,
            'raid_boss_id' => $secondMonster->id,
        ]);

        $this->createEvent([
            'raid_id' => $firstRaid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
        ]);
        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $firstRaid->id,
            'raid_boss_id' => $firstRaidBoss->id,
            'attacks_left' => 0,
            'damage_dealt' => 100,
            'killed_boss' => false,
        ]);
        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $firstRaid->id,
            'raid_boss_id' => $secondRaidBoss->id,
            'attacks_left' => 2,
            'damage_dealt' => 50,
            'killed_boss' => false,
        ]);

        Artisan::call('reset:daily-raid-attack-limits');

        $this->assertSame(5, RaidBossParticipation::where('character_id', $character->id)
            ->where('raid_id', $firstRaid->id)
            ->where('raid_boss_id', $firstRaidBoss->id)
            ->first()
            ->attacks_left);
        $this->assertSame(5, RaidBossParticipation::where('character_id', $character->id)
            ->where('raid_id', $firstRaid->id)
            ->where('raid_boss_id', $secondRaidBoss->id)
            ->first()
            ->attacks_left);
    }

    public function testKilledRaidParticipationDoesNotPreventAnotherRaidFromResetting(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $killedMonster = $this->createMonster();
        $activeMonster = $this->createMonster();
        $item = $this->createItem();
        $killedLocation = $this->createLocation();
        $activeLocation = $this->createLocation([
            'x' => 32,
            'y' => 32,
        ]);
        $killedRaid = $this->createRaid([
            'raid_boss_id' => $killedMonster->id,
            'raid_monster_ids' => [$killedMonster->id],
            'raid_boss_location_id' => $killedLocation->id,
            'corrupted_location_ids' => [$killedLocation->id],
            'artifact_item_id' => $item->id,
        ]);
        $activeRaid = $this->createRaid([
            'raid_boss_id' => $activeMonster->id,
            'raid_monster_ids' => [$activeMonster->id],
            'raid_boss_location_id' => $activeLocation->id,
            'corrupted_location_ids' => [$activeLocation->id],
            'artifact_item_id' => $item->id,
        ]);

        $this->createEvent([
            'raid_id' => $killedRaid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
        ]);
        $this->createEvent([
            'raid_id' => $activeRaid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
        ]);
        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $killedRaid->id,
            'attacks_left' => 0,
            'damage_dealt' => 100,
            'killed_boss' => true,
        ]);
        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $activeRaid->id,
            'attacks_left' => 0,
            'damage_dealt' => 50,
            'killed_boss' => false,
        ]);

        Artisan::call('reset:daily-raid-attack-limits');

        $this->assertSame(0, RaidBossParticipation::where('character_id', $character->id)
            ->where('raid_id', $killedRaid->id)
            ->first()
            ->attacks_left);
        $this->assertSame(5, RaidBossParticipation::where('character_id', $character->id)
            ->where('raid_id', $activeRaid->id)
            ->first()
            ->attacks_left);
    }

    public function testDoNotResetRaidFightWhenNoEvent()
    {
        Event::fake();

        Artisan::call('reset:daily-raid-attack-limits');

        Event::assertNotDispatched(GlobalMessageEvent::class);
    }

    public function testUpdateRaidAttacksLeftBroadcastsMonsterIdNotRaidBossPrimaryKey(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster();
        $item = $this->createItem();
        $location = $this->createLocation();
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
        ]);
        $this->createEvent([
            'raid_id' => $raid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
        ]);

        RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $monster->id,
        ]);

        $raidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $monster->id,
        ]);

        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $raidBoss->id,
            'attacks_left' => 0,
            'damage_dealt' => 500,
            'killed_boss' => false,
        ]);

        Artisan::call('reset:daily-raid-attack-limits');

        $this->assertSame(5, RaidBossParticipation::where('character_id', $character->id)
            ->where('raid_id', $raid->id)
            ->where('raid_boss_id', $raidBoss->id)
            ->first()
            ->attacks_left);

        Event::assertDispatched(UpdateRaidAttacksLeft::class, function (UpdateRaidAttacksLeft $event) use ($monster, $raidBoss) {
            return $event->raidBossId === $monster->id
                && $event->raidBossId !== $raidBoss->id;
        });
    }

    public function testDoNotresetRaidFightWhenRaidBossIsDead()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster();
        $item = $this->createItem();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
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

        Event::assertNotDispatched(GlobalMessageEvent::class);
    }
}
