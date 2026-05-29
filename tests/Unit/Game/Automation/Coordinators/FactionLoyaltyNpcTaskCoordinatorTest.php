<?php

namespace Tests\Unit\Game\Automation\Coordinators;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Values\MapNameValue;
use App\Game\Automation\Coordinators\FactionLoyaltyNpcTaskCoordinator;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class FactionLoyaltyNpcTaskCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    private ?FactionLoyaltyNpcTaskCoordinator $coordinator = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?Character $character = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
                $mock->shouldReceive('canWalkOnWater')->andReturn(false);
                $mock->shouldReceive('canWalkOnDeathWater')->andReturn(false);
                $mock->shouldReceive('canWalkOnMagma')->andReturn(false);
                $mock->shouldReceive('isPurgatoryWater')->andReturn(false);
                $mock->shouldReceive('isTwistedMemoriesWater')->andReturn(false);
                $mock->shouldReceive('isDelusionalMemoriesWater')->andReturn(false);
                $mock->shouldReceive('getTileColor')->andReturn('000');
            })
        );

        Cache::put('monsters', [
            MapNameValue::SURFACE => [],
        ]);

        $this->coordinator = resolve(FactionLoyaltyNpcTaskCoordinator::class);

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->createSessionForCharacter()
            ->getCharacter();

        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character, 2, 3)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
    }

    protected function tearDown(): void
    {
        Cache::forget('monsters');

        $this->coordinator = null;
        $this->factionLoyaltyFactory = null;
        $this->character = null;
        $this->factionLoyaltyAutomation = null;
        $this->factionLoyaltyNpc = null;

        parent::tearDown();
    }

    public function test_resolve_npc_keeps_current_npc_when_it_has_incomplete_tasks(): void
    {
        Event::fake();

        $resolvedNpc = $this->coordinator
            ->setUp($this->character, $this->factionLoyaltyAutomation)
            ->resolveNpc();

        $this->assertEquals($this->factionLoyaltyNpc->id, $resolvedNpc->id);
        $this->assertFalse($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_switches_to_same_map_npc_when_current_npc_is_complete(): void
    {
        Event::fake();

        $currentFactionLoyalty = $this->factionLoyaltyNpc->factionLoyalty;
        $sameMapNpc = $currentFactionLoyalty
            ->factionLoyaltyNpcs()
            ->where('id', '!=', $this->factionLoyaltyNpc->id)
            ->first();

        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

        foreach ($fameTasks as $index => $fameTask) {
            $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
        }

        $this->factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
            'fame_tasks' => $fameTasks,
        ]);

        $resolvedNpc = $this->coordinator
            ->setUp($this->character, $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->assertEquals($sameMapNpc->id, $resolvedNpc->id);
        $this->assertEquals($sameMapNpc->id, $this->factionLoyaltyAutomation->refresh()->faction_loyalty_npc_id);
        $this->assertTrue($resolvedNpc->refresh()->currently_helping);
        $this->assertFalse($this->factionLoyaltyNpc->refresh()->currently_helping);
    }

    public function test_resolve_npc_dispatches_log_when_switching_to_same_map_npc(): void
    {
        Event::fake();

        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

        foreach ($fameTasks as $index => $fameTask) {
            $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
        }

        $this->factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
            'fame_tasks' => $fameTasks,
        ]);

        $this->coordinator
            ->setUp($this->character, $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function test_resolve_npc_travels_pledges_and_assists_existing_faction_loyalty_npc(): void
    {
        Event::fake();

        $gameMaps = $this->factionLoyaltyFactory->getGameMaps();

        $gameMaps[1]->update([
            'name' => MapNameValue::SURFACE,
            'can_traverse' => true,
        ]);

        $currentFactionLoyalty = $this->factionLoyaltyNpc->factionLoyalty;

        foreach ($currentFactionLoyalty->factionLoyaltyNpcs as $factionLoyaltyNpc) {
            $fameTasks = $factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

            foreach ($fameTasks as $index => $fameTask) {
                $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
            }

            $factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
                'fame_tasks' => $fameTasks,
            ]);
        }

        $existingFactionLoyalty = $this->character
            ->refresh()
            ->factionLoyalties()
            ->where('id', '!=', $currentFactionLoyalty->id)
            ->first();

        $resolvedNpc = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->assertEquals($existingFactionLoyalty->id, $resolvedNpc->faction_loyalty_id);
        $this->assertEquals($gameMaps[1]->id, $this->character->refresh()->map->game_map_id);
        $this->assertTrue($existingFactionLoyalty->refresh()->is_pledged);
        $this->assertTrue($resolvedNpc->refresh()->currently_helping);
    }

    public function test_resolve_npc_ends_when_existing_faction_loyalty_npc_cannot_be_reached(): void
    {
        Event::fake();

        $currentFactionLoyalty = $this->factionLoyaltyNpc->factionLoyalty;

        foreach ($currentFactionLoyalty->factionLoyaltyNpcs as $factionLoyaltyNpc) {
            $fameTasks = $factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

            foreach ($fameTasks as $index => $fameTask) {
                $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
            }

            $factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
                'fame_tasks' => $fameTasks,
            ]);
        }

        $resolvedNpc = $this->coordinator
            ->setUp($this->character, $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->assertNull($resolvedNpc);
        $this->assertTrue($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_ends_when_existing_faction_loyalty_npc_faction_is_not_maxed(): void
    {
        Event::fake();

        $this->makeExistingFactionLoyaltyNpcAvailableButNotPledgeable();

        $resolvedNpc = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->assertNull($resolvedNpc);
        $this->assertTrue($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_does_not_travel_to_existing_faction_loyalty_npc_faction_when_faction_is_not_maxed(): void
    {
        Event::fake();

        $gameMaps = $this->factionLoyaltyFactory->getGameMaps();

        $this->makeExistingFactionLoyaltyNpcAvailableButNotPledgeable();

        $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->assertEquals($gameMaps[0]->id, $this->character->refresh()->map->game_map_id);
    }

    public function test_resolve_npc_does_not_pledge_existing_faction_loyalty_when_faction_is_not_maxed(): void
    {
        Event::fake();

        $existingFactionLoyalty = $this->makeExistingFactionLoyaltyNpcAvailableButNotPledgeable();

        $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->assertFalse($existingFactionLoyalty->refresh()->is_pledged);
    }

    public function test_resolve_npc_does_not_assist_existing_faction_loyalty_npc_when_faction_is_not_maxed(): void
    {
        Event::fake();

        $existingFactionLoyalty = $this->makeExistingFactionLoyaltyNpcAvailableButNotPledgeable();

        $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->assertFalse($existingFactionLoyalty->refresh()->factionLoyaltyNpcs()->where('currently_helping', true)->exists());
    }

    public function test_resolve_npc_dispatches_no_available_faction_message_when_existing_faction_loyalty_npc_faction_is_not_maxed(): void
    {
        Event::fake();

        $this->makeExistingFactionLoyaltyNpcAvailableButNotPledgeable();

        $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event): bool {
            return $event->message === 'There are no other factions for you to pledge to. You have not maxed out other factions on other maps.';
        });
    }

    public function test_resolve_npc_travels_pledges_and_assists_new_faction_loyalty_npc(): void
    {
        Event::fake();

        $gameMaps = $this->factionLoyaltyFactory->getGameMaps();

        foreach ($this->factionLoyaltyFactory->getFactionLoyaltyNpcs() as $factionLoyaltyNpc) {
            $fameTasks = $factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

            foreach ($fameTasks as $index => $fameTask) {
                $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
            }

            $factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
                'fame_tasks' => $fameTasks,
            ]);
        }

        $newFaction = Faction::create([
            'character_id' => $this->character->id,
            'game_map_id' => $gameMaps[0]->id,
            'current_level' => 5,
            'current_points' => 0,
            'points_needed' => 1000,
            'maxed' => true,
            'title' => null,
        ]);

        $resolvedNpc = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $newFactionLoyalty = FactionLoyalty::where('character_id', $this->character->id)
            ->where('faction_id', $newFaction->id)
            ->first();

        $this->assertNotNull($resolvedNpc);
        $this->assertNotNull($newFactionLoyalty);
        $this->assertTrue($newFactionLoyalty->is_pledged);
        $this->assertTrue($resolvedNpc->refresh()->currently_helping);
    }

    public function test_resolve_npc_skips_new_faction_when_it_cannot_be_reached(): void
    {
        Event::fake();

        foreach ($this->factionLoyaltyFactory->getFactionLoyaltyNpcs() as $factionLoyaltyNpc) {
            $fameTasks = $factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

            foreach ($fameTasks as $index => $fameTask) {
                $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
            }

            $factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
                'fame_tasks' => $fameTasks,
            ]);
        }

        $gameMaps = $this->factionLoyaltyFactory->getGameMaps();

        Faction::create([
            'character_id' => $this->character->id,
            'game_map_id' => $gameMaps[1]->id,
            'current_level' => 5,
            'current_points' => 0,
            'points_needed' => 1000,
            'maxed' => true,
            'title' => null,
        ]);

        $resolvedNpc = $this->coordinator
            ->setUp($this->character->refresh(), $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->assertNull($resolvedNpc);
        $this->assertTrue($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_ends_when_no_incomplete_tasks_are_available(): void
    {
        Event::fake();

        foreach ($this->factionLoyaltyFactory->getFactionLoyaltyNpcs() as $factionLoyaltyNpc) {
            $fameTasks = $factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

            foreach ($fameTasks as $index => $fameTask) {
                $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
            }

            $factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
                'fame_tasks' => $fameTasks,
            ]);
        }

        $resolvedNpc = $this->coordinator
            ->setUp($this->character, $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->assertNull($resolvedNpc);
        $this->assertTrue($this->coordinator->shouldEndAutomation());
    }

    public function test_resolve_npc_dispatches_log_when_automation_ends(): void
    {
        Event::fake();

        foreach ($this->factionLoyaltyFactory->getFactionLoyaltyNpcs() as $factionLoyaltyNpc) {
            $fameTasks = $factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

            foreach ($fameTasks as $index => $fameTask) {
                $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
            }

            $factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
                'fame_tasks' => $fameTasks,
            ]);
        }

        $this->coordinator
            ->setUp($this->character, $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function test_should_end_automation_is_reset_when_coordinator_is_set_up_again(): void
    {
        Event::fake();

        foreach ($this->factionLoyaltyFactory->getFactionLoyaltyNpcs() as $factionLoyaltyNpc) {
            $fameTasks = $factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

            foreach ($fameTasks as $index => $fameTask) {
                $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
            }

            $factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
                'fame_tasks' => $fameTasks,
            ]);
        }

        $this->coordinator
            ->setUp($this->character, $this->factionLoyaltyAutomation->refresh())
            ->resolveNpc();

        $this->coordinator->setUp($this->character, $this->factionLoyaltyAutomation->refresh());

        $this->assertFalse($this->coordinator->shouldEndAutomation());
    }

    public function completeFactionLoyaltyTasks(FactionLoyalty $factionLoyalty): void
    {
        foreach ($factionLoyalty->factionLoyaltyNpcs as $factionLoyaltyNpc) {
            $fameTasks = $factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

            foreach ($fameTasks as $index => $fameTask) {
                $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
            }

            $factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
                'fame_tasks' => $fameTasks,
            ]);
        }
    }

    public function makeExistingFactionLoyaltyNpcAvailableButNotPledgeable(): FactionLoyalty
    {
        $gameMaps = $this->factionLoyaltyFactory->getGameMaps();

        $gameMaps[1]->update([
            'name' => MapNameValue::SURFACE,
            'can_traverse' => true,
        ]);

        $currentFactionLoyalty = $this->factionLoyaltyNpc->factionLoyalty;

        $this->completeFactionLoyaltyTasks($currentFactionLoyalty);

        $existingFactionLoyalty = $this->character
            ->refresh()
            ->factionLoyalties()
            ->where('id', '!=', $currentFactionLoyalty->id)
            ->first();

        $existingFactionLoyalty->faction->update([
            'current_level' => 4,
            'maxed' => false,
        ]);

        return $existingFactionLoyalty->refresh();
    }
}
