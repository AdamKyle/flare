<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\ItemSkill;
use App\Flare\Models\RaidBoss;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\BattleRewardProcessing\Jobs\RaidBossRewardHandler;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use ReflectionMethod;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateRaidBossParticipation;

class RaidBossRewardHandlerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, CreateMonster, CreateRaid, CreateRaidBossParticipation, RefreshDatabase;

    public function test_only_killed_raid_boss_participations_are_zeroed(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $firstMonster = $this->createMonster();
        $secondMonster = $this->createMonster();
        $otherRaidMonster = $this->createMonster();
        $location = $this->createLocation();
        $otherLocation = $this->createLocation();
        $artifact = $this->createItem();
        $raid = $this->createRaid([
            'raid_boss_id' => $firstMonster->id,
            'raid_boss_location_id' => $location->id,
            'artifact_item_id' => $artifact->id,
        ]);
        $otherRaid = $this->createRaid([
            'raid_boss_id' => $otherRaidMonster->id,
            'raid_boss_location_id' => $otherLocation->id,
            'artifact_item_id' => $artifact->id,
        ]);
        $firstRaidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $firstMonster->id,
        ]);
        $secondRaidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $secondMonster->id,
        ]);
        $otherRaidBoss = RaidBoss::create([
            'raid_id' => $otherRaid->id,
            'raid_boss_id' => $otherRaidMonster->id,
        ]);
        $killedBossParticipation = $this->createRaidBossParticipation([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $firstRaidBoss->id,
            'attacks_left' => 3,
        ]);
        $siblingBossParticipation = $this->createRaidBossParticipation([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $secondRaidBoss->id,
            'attacks_left' => 4,
        ]);
        $otherRaidParticipation = $this->createRaidBossParticipation([
            'character_id' => $character->id,
            'raid_id' => $otherRaid->id,
            'raid_boss_id' => $otherRaidBoss->id,
            'attacks_left' => 5,
        ]);

        $method = new ReflectionMethod(RaidBossRewardHandler::class, 'zeroKilledBossParticipations');
        $method->invoke(new RaidBossRewardHandler($character->id, $firstMonster->id, $raid->id), $raid, $firstRaidBoss);

        $this->assertSame(0, $killedBossParticipation->refresh()->attacks_left);
        $this->assertSame(4, $siblingBossParticipation->refresh()->attacks_left);
        $this->assertSame(5, $otherRaidParticipation->refresh()->attacks_left);
    }

    public function test_update_raid_attacks_left_includes_raid_boss_id_as_monster_id_when_boss_killed(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $firstMonster = $this->createMonster();
        $location = $this->createLocation();
        $artifact = $this->createItem();
        $raid = $this->createRaid([
            'raid_boss_id' => $firstMonster->id,
            'raid_boss_location_id' => $location->id,
            'artifact_item_id' => $artifact->id,
        ]);
        $firstRaidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $firstMonster->id,
        ]);
        $this->createRaidBossParticipation([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $firstRaidBoss->id,
            'attacks_left' => 3,
            'damage_dealt' => 500,
        ]);

        $method = new ReflectionMethod(RaidBossRewardHandler::class, 'zeroKilledBossParticipations');
        $method->invoke(new RaidBossRewardHandler($character->id, $firstMonster->id, $raid->id), $raid, $firstRaidBoss);

        Event::assertDispatched(UpdateRaidAttacksLeft::class, function (UpdateRaidAttacksLeft $event) use ($firstMonster) {
            return $event->raidBossId === $firstMonster->id;
        });
    }

    public function test_gear_reward_only_considers_killed_boss_participations(): void
    {
        Event::fake();

        $charA = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $charB = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $firstMonster = $this->createMonster();
        $secondMonster = $this->createMonster();
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $this->createItem(['specialty_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER]);
        $raid = $this->createRaid([
            'raid_boss_id' => $firstMonster->id,
            'raid_boss_location_id' => $location->id,
            'item_specialty_reward_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER,
        ]);
        $firstRaidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $firstMonster->id,
        ]);
        $secondRaidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $secondMonster->id,
        ]);
        $this->createRaidBossParticipation([
            'character_id' => $charA->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $secondRaidBoss->id,
            'damage_dealt' => 100,
        ]);
        $this->createRaidBossParticipation([
            'character_id' => $charB->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $firstRaidBoss->id,
            'damage_dealt' => 9999,
        ]);

        $method = new ReflectionMethod(RaidBossRewardHandler::class, 'giveGearReward');
        $method->invoke(
            new RaidBossRewardHandler($charA->id, $secondMonster->id, $raid->id),
            $raid,
            $secondRaidBoss,
        );

        Event::assertDispatched(GlobalMessageEvent::class, function (GlobalMessageEvent $event) use ($charA) {
            return str_contains($event->message, $charA->name);
        });

        Event::assertNotDispatched(GlobalMessageEvent::class, function (GlobalMessageEvent $event) use ($charB) {
            return str_contains($event->message, $charB->name);
        });
    }

    public function test_handle_uses_killed_monster_id_instead_of_raid_default_boss(): void
    {
        Event::fake();

        $charA = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $charB = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $location = $this->createLocation();

        $itemSkill = ItemSkill::create([
            'name' => 'Test Skill',
            'description' => 'Test',
            'max_level' => 1,
            'total_kills_needed' => 0,
        ]);
        $artifactItem = $this->createItem(['type' => 'artifact', 'item_skill_id' => $itemSkill->id]);

        $bossAMonster = $this->createMonster();
        $bossBMonster = $this->createMonster();

        $this->createItem(['specialty_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER]);

        $dummyRaid = $this->createRaid([
            'raid_boss_id' => $bossAMonster->id,
            'raid_boss_location_id' => $location->id,
        ]);
        RaidBoss::create(['raid_id' => $dummyRaid->id, 'raid_boss_id' => $bossAMonster->id]);
        RaidBoss::create(['raid_id' => $dummyRaid->id, 'raid_boss_id' => $bossBMonster->id]);

        $raid = $this->createRaid([
            'raid_boss_id' => $bossAMonster->id,
            'raid_boss_location_id' => $location->id,
            'artifact_item_id' => $artifactItem->id,
            'item_specialty_reward_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER,
        ]);

        $bossARaidBoss = RaidBoss::create(['raid_id' => $raid->id, 'raid_boss_id' => $bossAMonster->id]);
        $bossBRaidBoss = RaidBoss::create(['raid_id' => $raid->id, 'raid_boss_id' => $bossBMonster->id]);

        $bossAParticipation = $this->createRaidBossParticipation([
            'character_id' => $charA->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $bossARaidBoss->id,
            'damage_dealt' => 9999,
            'attacks_left' => 3,
        ]);

        $bossBParticipation = $this->createRaidBossParticipation([
            'character_id' => $charB->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $bossBRaidBoss->id,
            'damage_dealt' => 100,
            'attacks_left' => 2,
        ]);

        $battleEventHandler = $this->createMock(BattleEventHandler::class);

        (new RaidBossRewardHandler($charB->id, $bossBMonster->id, $raid->id))->handle($battleEventHandler);

        $this->assertSame(0, $bossBParticipation->refresh()->attacks_left);
        $this->assertSame(3, $bossAParticipation->refresh()->attacks_left);

        Event::assertDispatched(UpdateRaidAttacksLeft::class, function (UpdateRaidAttacksLeft $event) use ($bossBMonster) {
            return $event->raidBossId === $bossBMonster->id;
        });

        Event::assertNotDispatched(UpdateRaidAttacksLeft::class, function (UpdateRaidAttacksLeft $event) use ($bossBRaidBoss) {
            return $event->raidBossId === $bossBRaidBoss->id;
        });

        Event::assertDispatched(GlobalMessageEvent::class, function (GlobalMessageEvent $event) use ($charB) {
            return str_contains($event->message, $charB->name);
        });

        Event::assertNotDispatched(GlobalMessageEvent::class, function (GlobalMessageEvent $event) use ($charA) {
            return str_contains($event->message, $charA->name) && str_contains($event->message, 'Congratulations');
        });
    }
}
