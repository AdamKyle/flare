<?php

namespace Tests\Feature\Admin\Adventures;

use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Adventure;
use App\Flare\Models\GameMap;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Setup\Character\CharacterFactory;

class AdventuresControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateLocation,
        CreateMonster,
        CreateAdventure;

    private $user;

    private $item;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->createMonster();

        $this->createLocation([
            'name'                 => 'Apples',
            'game_map_id'          => GameMap::create([
                'name' => 'Apples',
                'path' => 'test',
                'default' => true,
                'kingdom_color' => '#ffffff',
            ])->id,
            'quest_reward_item_id' => null,
            'description'          => 'test',
            'is_port'              => false,
            'x'                    => 1,
            'y'                    => 1,
        ]);

        $this->item = $this->createItem([
            'name'          => 'Sample',
            'type'          => 'quest',
            'base_damage'   => null,
            'cost'          => null,
            'crafting_type' => null,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testAdminCanSeeAdventuresPage()
    {
        $this->actingAs($this->user)->visit(route('adventures.list'))->see('Adventures');
    }

    public function testNonAdminCannotSeeAdventuresPage()
    {

        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $this->actingAs($user)->visit(route('game'))->visit(route('adventures.list'))->see('You don\'t have permission to view that.');
    }

    public function testCanCreateAdventure()
    {
        $this->actingAs($this->user)->visit(route('adventures.create'))->see('Create Adventure')->submitForm('Create Adventure', [
            'name'             => 'Sample Adventure',
            'description'      => 'sample',
            'location_ids'     => [Location::first()->id],
            'monster_ids'      => [Monster::first()->id],
            'reward_item_id'   => $this->item->id,
            'levels'           => 1,
            'time_per_level'   => 1,
            'gold_rush_chance' => 0.01,
            'item_find_chance' => 0.01,
            'skill_exp_bonus'  => 0.01,
        ])->see('Sample Adventure created!');

        // Make sure the adventure was actually created:

        $this->assertNotNull(Adventure::first());
        $this->assertEquals(Adventure::first()->name, 'Sample Adventure');
    }

    public function testCannotCreateAdventure()
    {
        $this->actingAs($this->user)->visit(route('adventures.create'))
                                    ->see('Create Adventure')
                                    ->submitForm('Create Adventure')
                                    ->see('Adventure name is required.')
                                    ->see('Adventure needs at least one location.')
                                    ->see('Adventure needs at least one monster.')
                                    ->see('Adventure levels is required.')
                                    ->see('Adventure time per level is required.');

        // Make sure the adventure was not created:
        $this->assertNull(Adventure::first());
    }

    public function testCanSeeAdventure()
    {
        $this->createNewAdventure();

        $this->actingAs($this->user)->visit(route('adventures.adventure', [
            'adventure' => Adventure::first()->id,
        ]))->see(Adventure::first()->name);

    }

    public function testCanUpdateAdventure() {
        $adventure = $this->createNewAdventure();

        $this->actingAs($this->user)->visit(route('adventure.edit', [
            'adventure' => $adventure->id
        ]))->see('Edit Adventure: ' . $adventure->name)->submitForm('Update Adventure', [
            'name'             => 'New Adventure Name',
            'description'      => 'New Description',
            'location_ids'     => [Location::first()->id],
            'monster_ids'      => [Monster::first()->id],
            'reward_item_id'   => Item::first()->id,
            'levels'           => 1,
            'time_per_level'   => 1,
            'gold_rush_chance' => 0.01,
            'item_find_chance' => 0.01,
            'skill_exp_bonus'  => 0.01,
        ])->see('New Adventure Name updated!');

        // Make sure the adventure was actually updated:
        $this->assertEquals($adventure->refresh()->name, 'New Adventure Name');
    }

    public function testCannotUpdateAdventure() {
        $adventure = $this->createNewAdventure();

        $this->actingAs($this->user)->visit(route('adventure.edit', [
            'adventure' => $adventure->id
        ]))->see('Edit Adventure: ' . $adventure->name)
           ->submitForm('Update Adventure')
           ->see('Adventure needs at least one location.');
    }

    public function testPublishAdventure() {
        $adventure = $this->createNewAdventure(null, 1, 'Sample', false);

        $response = $this->actingAs($this->user)->post(route('adventure.publish', [
            'adventure' => $adventure->id
        ]))->response;

        $this->assertTrue($adventure->refresh()->published);
    }
}
