<?php

use App\Flare\Models\InfoPage;
use App\Flare\Models\ItemSkill;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateRaid;

uses(
    CreateClass::class,
    CreateGameBuilding::class,
    CreateGameBuildingUnit::class,
    CreateGameClassSpecial::class,
    CreateGameLocationGemParamter::class,
    CreateGameMap::class,
    CreateGameMapGemParamter::class,
    CreateGameSkill::class,
    CreateGameUnit::class,
    CreateItem::class,
    CreateItemAffix::class,
    CreateLocation::class,
    CreateMonster::class,
    CreateNpc::class,
    CreatePassiveSkill::class,
    CreateQuest::class,
    CreateRace::class,
    CreateRaid::class,
);

test('search without a query redirects to the info home page', function () {
    $response = $this->get(route('info.search'));

    $response->assertRedirect(route('info.page', ['pageName' => 'home']));
});

test('search finds pages whose sections match the query and excludes non matching pages', function () {
    InfoPage::create([
        'page_name' => 'matching-page',
        'page_sections' => [[
            'order' => 1,
            'content' => 'A page about dragons.',
            'content_image_path' => null,
            'live_wire_component' => null,
            'item_table_type' => null,
        ]],
    ]);
    InfoPage::create([
        'page_name' => 'non-matching-page',
        'page_sections' => [[
            'order' => 1,
            'content' => 'A page about kingdoms.',
            'content_image_path' => null,
            'live_wire_component' => null,
            'item_table_type' => null,
        ]],
    ]);

    $response = $this->get(route('info.search', ['info_search' => 'dragons']));

    $response->assertOk();
    $response->assertViewIs('information.search-results');
    $response->assertSee('matching-page');
    $response->assertDontSee('non-matching-page');
});

test('page renders class skills table for a stored live wire component alias', function () {
    $class = $this->createClass(['name' => 'Warrior']);
    $this->createGameSkill(['name' => 'Shield Bash', 'game_class_id' => $class->id]);

    InfoPage::create([
        'page_name' => 'class-skills-page',
        'page_sections' => [[
            'order' => 1,
            'content' => '<p>Class skills below.</p>',
            'content_image_path' => null,
            'live_wire_component' => 'info.skills.class-skills',
            'item_table_type' => null,
        ]],
    ]);

    $response = $this->get(route('info.page', ['pageName' => 'class-skills-page']));

    $response->assertSee('Shield Bash');
    $response->assertSee('Warrior');
});

test('page with unknown live wire component alias renders safely', function () {
    InfoPage::create([
        'page_name' => 'unknown-alias-page',
        'page_sections' => [[
            'order' => 1,
            'content' => '<p>Nothing dynamic here.</p>',
            'content_image_path' => null,
            'live_wire_component' => 'info.does-not-exist',
            'item_table_type' => null,
        ]],
    ]);

    $response = $this->get(route('info.page', ['pageName' => 'unknown-alias-page']));

    $response->assertOk();
    $response->assertSee('Nothing dynamic here.');
});

test('page that does not exist returns a 404', function () {
    $response = $this->get(route('info.page', ['pageName' => 'does-not-exist']));

    $response->assertNotFound();
});

test('map gems list page renders', function () {
    $gemParamter = $this->createGameMapGemParamter(['name' => 'Ember Shard']);

    $response = $this->get(route('info.page.map-gems.list'));

    $response->assertOk();
    $response->assertSee('Ember Shard');
    $response->assertSee($gemParamter->gameMap->name);
});

test('map gems list search filters by name', function () {
    $this->createGameMapGemParamter(['name' => 'Ember Shard']);
    $this->createGameMapGemParamter(['name' => 'Frost Crystal']);

    $response = $this->get(route('info.page.map-gems.list', ['search' => 'Ember']));

    $response->assertOk();
    $response->assertSee('Ember Shard');
    $response->assertDontSee('Frost Crystal');
});

test('map gems list shows an empty state with no gems', function () {
    $response = $this->get(route('info.page.map-gems.list'));

    $response->assertOk();
    $response->assertSee('No map gems found.');
});

test('map gem show page renders', function () {
    $gemParamter = $this->createGameMapGemParamter(['name' => 'Ember Shard']);

    $response = $this->get(route('info.page.map-gems.show', $gemParamter));

    $response->assertOk();
    $response->assertSee('Ember Shard');
});

test('location gems list page renders', function () {
    $gemParamter = $this->createGameLocationGemParamter(['name' => 'Ember Shard']);

    $response = $this->get(route('info.page.location-gems.list'));

    $response->assertOk();
    $response->assertSee('Ember Shard');
    $response->assertSee($gemParamter->location->name);
});

test('location gems list search filters by name', function () {
    $this->createGameLocationGemParamter(['name' => 'Ember Shard']);
    $this->createGameLocationGemParamter(['name' => 'Frost Crystal']);

    $response = $this->get(route('info.page.location-gems.list', ['search' => 'Ember']));

    $response->assertOk();
    $response->assertSee('Ember Shard');
    $response->assertDontSee('Frost Crystal');
});

test('location gems list shows an empty state with no gems', function () {
    $response = $this->get(route('info.page.location-gems.list'));

    $response->assertOk();
    $response->assertSee('No location gems found.');
});

test('location gem show page renders', function () {
    $gemParamter = $this->createGameLocationGemParamter(['name' => 'Ember Shard']);

    $response = $this->get(route('info.page.location-gems.show', $gemParamter));

    $response->assertOk();
    $response->assertSee('Ember Shard');
});

test('race show page renders', function () {
    $race = $this->createRace(['name' => 'Human']);

    $response = $this->get(route('info.page.race', $race));

    $response->assertOk();
    $response->assertSee('Human');
});

test('class show page renders with class bonus details', function () {
    $class = $this->createClass(['name' => 'Fighter']);

    $response = $this->get(route('info.page.class', $class));

    $response->assertOk();
    $response->assertSee('Fighter');
});

test('map show page renders for the labyrinth plane', function () {
    $map = $this->createGameMap(['name' => 'Labyrinth']);

    $response = $this->get(route('info.page.map', $map));

    $response->assertOk();
});

test('map show page renders for the dungeons plane', function () {
    $map = $this->createGameMap(['name' => 'Dungeons']);

    $response = $this->get(route('info.page.map', $map));

    $response->assertOk();
});

test('map show page renders for the shadow plane', function () {
    $map = $this->createGameMap(['name' => 'Shadow Plane']);

    $response = $this->get(route('info.page.map', $map));

    $response->assertOk();
});

test('map show page renders for hell', function () {
    $map = $this->createGameMap(['name' => 'Hell']);

    $response = $this->get(route('info.page.map', $map));

    $response->assertOk();
});

test('map show page renders for purgatory', function () {
    $map = $this->createGameMap(['name' => 'Purgatory']);

    $response = $this->get(route('info.page.map', $map));

    $response->assertOk();
});

test('map show page renders for the ice plane', function () {
    $map = $this->createGameMap(['name' => 'The Ice Plane']);

    $response = $this->get(route('info.page.map', $map));

    $response->assertOk();
});

test('map show page renders for an unmapped plane name', function () {
    $map = $this->createGameMap(['name' => 'Twisted Memories']);

    $response = $this->get(route('info.page.map', $map));

    $response->assertOk();
});

test('skill show page renders', function () {
    $skill = $this->createGameSkill(['name' => 'Looting']);

    $response = $this->get(route('info.page.skill', $skill));

    $response->assertOk();
    $response->assertSee('Looting');
});

test('class specialty show page renders', function () {
    $class = $this->createClass();
    $classSpecial = $this->createGameClassSpecial(['game_class_id' => $class->id]);

    $response = $this->get(route('info.page.class-special', $classSpecial));

    $response->assertOk();
});

test('monster show page renders', function () {
    $monster = $this->createMonster(['name' => 'Goblin']);

    $response = $this->get(route('info.page.monster', $monster));

    $response->assertOk();
    $response->assertSee('Goblin');
});

test('location show page renders for a guest', function () {
    $map = $this->createGameMap();
    $location = $this->createLocation(['name' => 'Guest Field', 'game_map_id' => $map->id, 'quest_reward_item_id' => null, 'type' => null]);

    $response = $this->get(route('info.page.location', $location));

    $response->assertOk();
    $response->assertSee('Guest Field');
});

test('location show page renders without a quest reward item', function () {
    $map = $this->createGameMap();
    $location = $this->createLocation(['name' => 'Quiet Field', 'game_map_id' => $map->id, 'quest_reward_item_id' => null, 'type' => null]);

    $response = $this->actingAs((new CharacterFactory)->createBaseCharacter()->getCharacter()->user)->get(route('info.page.location', $location));

    $response->assertOk();
    $response->assertSee('Quiet Field');
});

test('location show page renders with a quest reward item', function () {
    $item = $this->createItem(['name' => 'Tarnished Locket']);
    $map = $this->createGameMap();
    $location = $this->createLocation([
        'name' => 'Forgotten Grove',
        'game_map_id' => $map->id,
        'quest_reward_item_id' => $item->id,
        'type' => null,
    ]);

    $response = $this->actingAs((new CharacterFactory)->createBaseCharacter()->getCharacter()->user)->get(route('info.page.location', $location));

    $response->assertOk();
    $response->assertSee('Forgotten Grove');
    $response->assertSee('Tarnished Locket');
});

test('location show page renders with a location type set', function () {
    $map = $this->createGameMap();
    $location = $this->createLocation([
        'name' => 'Tear in the Fabric of Time',
        'game_map_id' => $map->id,
        'quest_reward_item_id' => null,
        'type' => 4,
    ]);

    $response = $this->actingAs((new CharacterFactory)->createBaseCharacter()->getCharacter()->user)->get(route('info.page.location', $location));

    $response->assertOk();
    $response->assertSee('Tear in the Fabric of Time');
});

test('unit show page renders with its building', function () {
    $building = $this->createGameBuilding();
    $unit = $this->createGameUnit();
    $this->createGameBuildingUnit(['game_building_id' => $building->id, 'game_unit_id' => $unit->id, 'required_level' => 5]);

    $response = $this->get(route('info.page.unit', $unit));

    $response->assertOk();
});

test('building show page renders', function () {
    $building = $this->createGameBuilding(['name' => 'Barracks']);

    $response = $this->get(route('info.page.building', $building));

    $response->assertOk();
    $response->assertSee('Barracks');
});

test('item show page renders', function () {
    $item = $this->createItem(['name' => 'Ironclad Helm']);

    $response = $this->get(route('info.page.item', $item));

    $response->assertOk();
    $response->assertSee('Ironclad Helm');
});

test('affix show page renders', function () {
    $affix = $this->createItemAffix();

    $response = $this->get(route('info.page.affix', $affix));

    $response->assertOk();
});

test('npc show page renders', function () {
    $npc = $this->createNpc(['real_name' => 'Old Man Jenkins']);

    $response = $this->get(route('info.page.npc', $npc));

    $response->assertOk();
});

test('raid show page renders', function () {
    $monster = $this->createMonster();
    $location = $this->createLocation(['game_map_id' => $monster->game_map_id]);
    $artifactItem = $this->createItem();
    $raid = $this->createRaid([
        'raid_boss_id' => $monster->id,
        'raid_boss_location_id' => $location->id,
        'raid_monster_ids' => [$monster->id],
        'artifact_item_id' => $artifactItem->id,
    ]);

    $response = $this->get(route('info.page.raid', $raid));

    $response->assertOk();
});

test('quest show page renders without an unlocked skill', function () {
    $npc = $this->createNpc();
    $quest = $this->createQuest(['npc_id' => $npc->id, 'unlocks_skill' => false]);

    $response = $this->get(route('info.page.quest', $quest));

    $response->assertOk();
});

test('quest show page renders with its unlocked skill', function () {
    $npc = $this->createNpc();
    $lockedSkill = $this->createGameSkill(['type' => 1, 'is_locked' => true]);
    $quest = $this->createQuest(['npc_id' => $npc->id, 'unlocks_skill' => true, 'unlocks_skill_type' => 1]);

    $response = $this->get(route('info.page.quest', $quest));

    $response->assertOk();
    $response->assertViewHas('lockedSkill', fn ($skill) => $skill->id === $lockedSkill->id);
});

test('passive skill show page renders', function () {
    $passiveSkill = $this->createPassiveSkill(['name' => 'Kingdom Defence']);

    $response = $this->get(route('info.page.passive.skill', $passiveSkill));

    $response->assertOk();
    $response->assertSee('Kingdom Defence');
});

test('item skill show page renders', function () {
    $itemSkill = ItemSkill::create([
        'name' => 'Blade Mastery',
        'description' => 'Increases blade proficiency.',
        'max_level' => 10,
        'total_kills_needed' => 500,
    ]);

    $response = $this->get(route('info.page.item-skill.skill', $itemSkill));

    $response->assertOk();
    $response->assertSee('Blade Mastery');
});
