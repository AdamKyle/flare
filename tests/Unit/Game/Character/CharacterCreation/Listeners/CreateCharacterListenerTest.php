<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Listeners;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\MapNameValue;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\Events\CreateCharacterEvent;
use App\Game\Character\CharacterCreation\Listeners\CreateCharacterListener;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CreateCharacterListenerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateClass,
        CreateGameMap,
        CreateItem,
        CreateGameSkill,
        CreatePassiveSkill;

    public function testHandleCreatesCharacterThroughPipeline(): void
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE]);
        $this->createGameMap(['name' => MapNameValue::HELL]);
        $this->createGameMap(['name' => MapNameValue::PURGATORY]);

        $this->createItem([
            'name' => 'Rusty blade',
            'type' => 'sword',
            'base_damage' => 3,
            'skill_level_required' => 1,
        ]);

        $this->createGameSkill(['name' => 'General A', 'game_class_id' => null]);

        $race = $this->createRace();
        $class = $this->createClass(['name' => 'Fighter', 'damage_stat' => 'str']);

        $user = $this->createUser();

        $this->app->instance(BuildCharacterAttackTypes::class, tap(Mockery::mock(BuildCharacterAttackTypes::class), function ($m) {
            $m->shouldReceive('buildCache')->once()->with(Mockery::type(Character::class));
        }));

        $request = Request::create('/register', 'POST', [
            'name'  => 'ListenerUser',
            'race'  => $race->id,
            'class' => $class->id,
        ]);

        $listener = app(CreateCharacterListener::class);

        $event = new CreateCharacterEvent($user, $surface, $request);

        $listener->handle($event);

        $character = Character::query()
            ->with(['gemBag', 'inventory.slots', 'map', 'factions', 'classRanks', 'classRanks.weaponMasteries'])
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($character);
        $this->assertSame('ListenerUser', $character->name);
        $this->assertSame($race->id, $character->game_race_id);
        $this->assertSame($class->id, $character->game_class_id);
        $this->assertNotNull($character->gemBag);
        $this->assertNotNull($character->inventory);

        $this->assertSame(1, $character->inventory->slots->count());
        $slot = $character->inventory->slots->first();
        $this->assertTrue((bool) $slot->equipped);
        $this->assertSame('left-hand', $slot->position);
        $starterId = Item::query()->where('type', 'sword')->where('skill_level_required', 1)->value('id');
        $this->assertSame($starterId, $slot->item_id);

        $this->assertNotNull($character->map);
        $this->assertSame($surface->id, $character->map->game_map_id);
    }

    public function testHandleUsesRaceAndClassFromRequestIds(): void
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE]);
        $this->createGameMap(['name' => MapNameValue::HELL]);

        $this->createGameSkill(['name' => 'General A', 'game_class_id' => null]);

        $raceA = $this->createRace();
        $raceB = $this->createRace();

        $classA = $this->createClass(['name' => 'Fighter', 'damage_stat' => 'str']);
        $classB = $this->createClass(['name' => 'Heretic', 'damage_stat' => 'int']);

        $mapping = ItemTypeMapping::getForClass($classB->name);
        $primaryType = is_array($mapping) ? $mapping[0] : $mapping;

        $this->createItem([
            'name' => 'Starter '.$primaryType,
            'type' => $primaryType,
            'base_damage' => 1,
            'skill_level_required' => 1,
        ]);

        $user = $this->createUser();

        $this->app->instance(BuildCharacterAttackTypes::class, tap(Mockery::mock(BuildCharacterAttackTypes::class), function ($m) {
            $m->shouldReceive('buildCache')->once()->with(Mockery::type(Character::class));
        }));

        $request = Request::create('/register', 'POST', [
            'name'  => 'ChosenCombo',
            'race'  => $raceB->id,
            'class' => $classB->id,
        ]);

        $listener = app(CreateCharacterListener::class);

        $event = new CreateCharacterEvent($user, $surface, $request);

        $listener->handle($event);

        $character = Character::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($character);
        $this->assertSame('ChosenCombo', $character->name);
        $this->assertSame($raceB->id, $character->game_race_id);
        $this->assertSame($classB->id, $character->game_class_id);
    }
}
