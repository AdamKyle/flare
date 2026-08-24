<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\ItemSkill;
use App\Flare\Models\RaidBoss;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\BattleRewardProcessing\Jobs\RaidBossRewardHandler;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

        $this->createItem(['specialty_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER->value]);

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
            'item_specialty_reward_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER->value,
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

        $battleEventHandler = $this->createStub(BattleEventHandler::class);
        $this->app->instance(BattleEventHandler::class, $battleEventHandler);

        RaidBossRewardHandler::dispatch($charB->id, $bossBMonster->id, $raid->id);

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
