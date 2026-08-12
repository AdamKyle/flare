<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline;

use App\Flare\Models\Character;
use App\Game\Character\CharacterCreation\Jobs\BuildCharacterCacheData;
use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CharacterCreationPipelineTest extends TestCase
{
    use CreateClass, CreateGameMap, CreateItem, CreateRace, CreateUser, RefreshDatabase;

    public function test_run_creates_a_character_through_every_step_and_dispatches_the_cache_build_job(): void
    {
        Bus::fake();

        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass(['name' => 'Fighter', 'damage_stat' => 'str']);
        $map = $this->createGameMap(['name' => MapName::SURFACE->value]);

        $this->createItem([
            'name' => 'Rusty blade',
            'type' => 'sword',
            'base_damage' => 3,
            'skill_level_required' => 1,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setMap($map)
            ->setCharacterName('Pipeline Hero')
            ->setNow(now());

        $result = resolve(CharacterCreationPipeline::class)->run($state);

        $character = $result->getCharacter();

        $this->assertNotNull($character);
        $this->assertSame('Pipeline Hero', $character->name);

        $reloaded = Character::query()->with(['inventory.slots', 'map', 'gemBag'])->find($character->id);

        $this->assertNotNull($reloaded->inventory);
        $this->assertSame(1, $reloaded->inventory->slots->count());
        $this->assertNotNull($reloaded->map);
        $this->assertSame($map->id, $reloaded->map->game_map_id);
        $this->assertNotNull($reloaded->gemBag);

        Bus::assertDispatched(fn (BuildCharacterCacheData $job): bool => true);
    }

    public function test_run_does_not_dispatch_the_cache_build_job_when_no_character_is_created(): void
    {
        Bus::fake();

        $state = app(CharacterBuildState::class)->setNow(now());

        $result = resolve(CharacterCreationPipeline::class)->run($state);

        $this->assertNull($result->getCharacter());

        Bus::assertNotDispatched(BuildCharacterCacheData::class);
    }
}
