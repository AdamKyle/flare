<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Models\Location;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\Raid;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
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

class RaidBossRewardHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function testOnlyKilledRaidBossParticipationsAreZeroed(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $firstMonster = Monster::factory()->create();
        $secondMonster = Monster::factory()->create();
        $otherRaidMonster = Monster::factory()->create();
        $location = Location::factory()->create();
        $otherLocation = Location::factory()->create();
        $artifact = Item::factory()->create();
        $raid = Raid::factory()->create([
            'raid_boss_id' => $firstMonster->id,
            'raid_boss_location_id' => $location->id,
            'artifact_item_id' => $artifact->id,
        ]);
        $otherRaid = Raid::factory()->create([
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
        $killedBossParticipation = RaidBossParticipation::factory()->create([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $firstRaidBoss->id,
            'attacks_left' => 3,
        ]);
        $siblingBossParticipation = RaidBossParticipation::factory()->create([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $secondRaidBoss->id,
            'attacks_left' => 4,
        ]);
        $otherRaidParticipation = RaidBossParticipation::factory()->create([
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

    public function testUpdateRaidAttacksLeftIncludesRaidBossIdAsMonsterIdWhenBossKilled(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $firstMonster = Monster::factory()->create();
        $location = Location::factory()->create();
        $artifact = Item::factory()->create();
        $raid = Raid::factory()->create([
            'raid_boss_id' => $firstMonster->id,
            'raid_boss_location_id' => $location->id,
            'artifact_item_id' => $artifact->id,
        ]);
        $firstRaidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $firstMonster->id,
        ]);
        RaidBossParticipation::factory()->create([
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

    public function testGearRewardOnlyConsidersKilledBossParticipations(): void
    {
        Event::fake();

        $charA = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $charB = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $firstMonster = Monster::factory()->create();
        $secondMonster = Monster::factory()->create();
        $gameMap = GameMap::factory()->create();
        $location = Location::factory()->create(['game_map_id' => $gameMap->id]);
        Item::factory()->create(['specialty_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER]);
        $raid = Raid::factory()->create([
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
        RaidBossParticipation::factory()->create([
            'character_id' => $charA->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $secondRaidBoss->id,
            'damage_dealt' => 100,
        ]);
        RaidBossParticipation::factory()->create([
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

    public function testHandleUsesKilledMonsterIdInsteadOfRaidDefaultBoss(): void
    {
        Event::fake();

        $charA = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $charB = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $location = Location::factory()->create();

        $itemSkill = ItemSkill::create([
            'name' => 'Test Skill',
            'description' => 'Test',
            'max_level' => 1,
            'total_kills_needed' => 0,
        ]);
        $artifactItem = Item::factory()->create(['type' => 'artifact', 'item_skill_id' => $itemSkill->id]);

        $bossAMonster = Monster::factory()->create();
        $bossBMonster = Monster::factory()->create();

        Item::factory()->create(['specialty_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER]);

        $dummyRaid = Raid::factory()->create([
            'raid_boss_id' => $bossAMonster->id,
            'raid_boss_location_id' => $location->id,
        ]);
        RaidBoss::create(['raid_id' => $dummyRaid->id, 'raid_boss_id' => $bossAMonster->id]);
        RaidBoss::create(['raid_id' => $dummyRaid->id, 'raid_boss_id' => $bossBMonster->id]);

        $raid = Raid::factory()->create([
            'raid_boss_id' => $bossAMonster->id,
            'raid_boss_location_id' => $location->id,
            'artifact_item_id' => $artifactItem->id,
            'item_specialty_reward_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER,
        ]);

        $bossARaidBoss = RaidBoss::create(['raid_id' => $raid->id, 'raid_boss_id' => $bossAMonster->id]);
        $bossBRaidBoss = RaidBoss::create(['raid_id' => $raid->id, 'raid_boss_id' => $bossBMonster->id]);

        $bossAParticipation = RaidBossParticipation::factory()->create([
            'character_id' => $charA->id,
            'raid_id' => $raid->id,
            'raid_boss_id' => $bossARaidBoss->id,
            'damage_dealt' => 9999,
            'attacks_left' => 3,
        ]);

        $bossBParticipation = RaidBossParticipation::factory()->create([
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
