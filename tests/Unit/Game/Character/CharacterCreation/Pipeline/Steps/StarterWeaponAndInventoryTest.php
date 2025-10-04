<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Game\Character\CharacterCreation\Pipeline\Steps\StarterWeaponAndInventory;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class StarterWeaponAndInventoryTest extends TestCase
{
    use CreateCharacter,
        CreateClass,
        CreateItem,
        CreateRace,
        CreateUser,
        RefreshDatabase;

    public function test_creates_gem_bag_inventory_starter_weapon_and_inventory_sets(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass([
            'name' => 'Fighter',
            'damage_stat' => 'str',
        ]);

        $this->createItem([
            'name' => 'Rusty blade',
            'type' => 'sword',
            'base_damage' => 3,
            'skill_level_required' => 1,
        ]);

        $character = $this->createCharacter([
            'damage_stat' => $class->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $class->id,
            'game_race_id' => $race->id,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setCharacter($character)
            ->setNow(now());

        $step = app(StarterWeaponAndInventory::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $reloaded = Character::query()->with(['gemBag', 'inventory.slots'])->find($character->id);
        $this->assertNotNull($reloaded);
        $this->assertNotNull($reloaded->gemBag);
        $this->assertNotNull($reloaded->inventory);

        $this->assertSame(1, $reloaded->inventory->slots->count());
        $slot = $reloaded->inventory->slots->first();
        $this->assertTrue((bool) $slot->equipped);
        $this->assertSame('left-hand', $slot->position);

        $starterId = Item::query()->where('type', 'sword')->where('skill_level_required', 1)->value('id');
        $this->assertSame($starterId, $slot->item_id);

        $this->assertSame(10, InventorySet::query()->where('character_id', $character->id)->count());
    }

    public function test_no_op_when_state_has_no_character(): void
    {
        $state = app(CharacterBuildState::class)->setNow(now());

        $step = app(StarterWeaponAndInventory::class);

        $result = $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });

        $this->assertInstanceOf(CharacterBuildState::class, $result);
        $this->assertNull($result->getCharacter());

        $this->assertSame(0, InventorySet::query()->count());
    }

    public function test_throws_when_no_starter_items_exist(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass([
            'name' => 'Fighter',
            'damage_stat' => 'str',
        ]);

        $character = $this->createCharacter([
            'damage_stat' => $class->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $class->id,
            'game_race_id' => $race->id,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setCharacter($character)
            ->setNow(now());

        $step = app(StarterWeaponAndInventory::class);

        $this->expectException(\RuntimeException::class);

        $step->process($state, function (CharacterBuildState $s) {
            return $s;
        });
    }

    public function test_prefers_first_weapon_type_when_multiple_mapped(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass([
            'name' => 'Heretic',
            'damage_stat' => 'int',
        ]);

        $this->createItem([
            'name' => 'Simple Wand',
            'type' => 'wand',
            'base_damage' => 1,
            'skill_level_required' => 1,
        ]);

        $this->createItem([
            'name' => 'Simple Stave',
            'type' => 'stave',
            'base_damage' => 1,
            'skill_level_required' => 1,
        ]);

        $character = $this->createCharacter([
            'damage_stat' => $class->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $class->id,
            'game_race_id' => $race->id,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setCharacter($character)
            ->setNow(now());

        $step = app(StarterWeaponAndInventory::class);
        $result = $step->process($state, fn (CharacterBuildState $s) => $s);

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $chosenId = $character->fresh('inventory.slots')->inventory->slots->first()->item_id;
        $chosenType = Item::query()->where('id', $chosenId)->value('type');

        $this->assertSame('wand', $chosenType);
    }

    public function test_uses_single_mapped_weapon_type(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass([
            'name' => 'Ranger',
            'damage_stat' => 'dex',
        ]);

        $this->createItem([
            'name' => 'Simple Bow',
            'type' => 'bow',
            'base_damage' => 1,
            'skill_level_required' => 1,
        ]);

        $character = $this->createCharacter([
            'damage_stat' => $class->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $class->id,
            'game_race_id' => $race->id,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setCharacter($character)
            ->setNow(now());

        $step = app(StarterWeaponAndInventory::class);
        $result = $step->process($state, fn (CharacterBuildState $s) => $s);

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $chosenId = $character->fresh('inventory.slots')->inventory->slots->first()->item_id;
        $chosenType = Item::query()->where('id', $chosenId)->value('type');

        $this->assertSame('bow', $chosenType);
    }

    public function test_falls_back_to_any_starter_weapon_when_no_mapping(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass([
            'name' => 'Mystic Adept',
            'damage_stat' => 'int',
        ]);

        $bowId = $this->createItem([
            'name' => 'Any Bow',
            'type' => 'bow',
            'base_damage' => 1,
            'skill_level_required' => 1,
        ])->id;

        $this->createItem([
            'name' => 'Any Dagger',
            'type' => 'dagger',
            'base_damage' => 1,
            'skill_level_required' => 1,
        ]);

        $character = $this->createCharacter([
            'damage_stat' => $class->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $class->id,
            'game_race_id' => $race->id,
        ]);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setCharacter($character)
            ->setNow(now());

        $step = app(StarterWeaponAndInventory::class);
        $result = $step->process($state, fn (CharacterBuildState $s) => $s);

        $this->assertInstanceOf(CharacterBuildState::class, $result);

        $chosenId = $character->fresh('inventory.slots')->inventory->slots->first()->item_id;

        $this->assertSame($bowId, $chosenId);
    }
}
