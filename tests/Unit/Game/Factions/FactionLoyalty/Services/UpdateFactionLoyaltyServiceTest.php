<?php

namespace Tests\Unit\Game\Factions\FactionLoyalty\Services;

use App\Game\Factions\FactionLoyalty\Services\UpdateFactionLoyaltyService;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class UpdateFactionLoyaltyServiceTest extends TestCase
{
    use CreateFactionLoyalty, CreateGameMap, CreateMonster, CreateNpc, RefreshDatabase;

    private ?UpdateFactionLoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(UpdateFactionLoyaltyService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_reassigns_bounty_task_monster_when_the_current_monster_is_on_the_wrong_map(): void
    {
        $delusionalMap = $this->createGameMap([
            'name' => MapName::DELUSIONAL_MEMORIES->value,
            'path' => 'delusional-memories',
        ]);

        $otherMap = $this->createGameMap([
            'name' => 'Some Other Map',
            'path' => 'some-other-map',
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $faction = $character->factions()->where('game_map_id', $delusionalMap->id)->first();

        $factionLoyalty = $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
            'is_pledged' => true,
        ]);

        $npc = $this->createNpc([
            'game_map_id' => $delusionalMap->id,
        ]);

        $factionLoyaltyNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 0,
            'kingdom_item_defence_bonus' => 0.025,
            'currently_helping' => false,
        ]);

        $correctMonster = $this->createMonster([
            'name' => 'Correct Map Monster',
            'game_map_id' => $delusionalMap->id,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'is_celestial_entity' => false,
            'only_for_location_type' => null,
        ]);

        $wrongMonster = $this->createMonster([
            'name' => 'Wrong Map Monster',
            'game_map_id' => $otherMap->id,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'is_celestial_entity' => false,
            'only_for_location_type' => null,
        ]);

        $factionLoyaltyNpcTask = $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'fame_tasks' => [
                [
                    'type' => 'bounty',
                    'monster_name' => $wrongMonster->name,
                    'monster_id' => $wrongMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
            ],
        ]);

        $this->service->updateFactionLoyaltyBountyTasks($character);

        $updatedTasks = $factionLoyaltyNpcTask->fresh()->fame_tasks;

        $this->assertSame($correctMonster->id, $updatedTasks[0]['monster_id']);
        $this->assertSame($correctMonster->name, $updatedTasks[0]['monster_name']);
    }

    public function test_retries_the_monster_pick_when_the_first_candidate_already_has_a_bounty_task(): void
    {
        $delusionalMap = $this->createGameMap([
            'name' => MapName::DELUSIONAL_MEMORIES->value,
            'path' => 'delusional-memories',
        ]);

        $otherMap = $this->createGameMap([
            'name' => 'Some Other Map',
            'path' => 'some-other-map',
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $faction = $character->factions()->where('game_map_id', $delusionalMap->id)->first();

        $factionLoyalty = $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
            'is_pledged' => true,
        ]);

        $npc = $this->createNpc([
            'game_map_id' => $delusionalMap->id,
        ]);

        $factionLoyaltyNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 0,
            'kingdom_item_defence_bonus' => 0.025,
            'currently_helping' => false,
        ]);

        $takenMonster = $this->createMonster([
            'name' => 'Already Taken Monster',
            'game_map_id' => $delusionalMap->id,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'is_celestial_entity' => false,
            'only_for_location_type' => null,
        ]);

        $freeMonster = $this->createMonster([
            'name' => 'Free Monster',
            'game_map_id' => $delusionalMap->id,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'is_celestial_entity' => false,
            'only_for_location_type' => null,
        ]);

        $wrongMonster = $this->createMonster([
            'name' => 'Wrong Map Monster',
            'game_map_id' => $otherMap->id,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'is_celestial_entity' => false,
            'only_for_location_type' => null,
        ]);

        $factionLoyaltyNpcTask = $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'fame_tasks' => [
                [
                    'type' => 'bounty',
                    'monster_name' => $takenMonster->name,
                    'monster_id' => $takenMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                [
                    'type' => 'bounty',
                    'monster_name' => $wrongMonster->name,
                    'monster_id' => $wrongMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
            ],
        ]);

        $service = Mockery::mock(UpdateFactionLoyaltyService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('pickRandomMonsterForMap')->andReturn($takenMonster, $freeMonster);

        $service->updateFactionLoyaltyBountyTasks($character);

        $updatedTasks = $factionLoyaltyNpcTask->fresh()->fame_tasks;

        $this->assertSame($takenMonster->id, $updatedTasks[0]['monster_id']);
        $this->assertSame($freeMonster->id, $updatedTasks[1]['monster_id']);
        $this->assertSame($freeMonster->name, $updatedTasks[1]['monster_name']);
    }
}
