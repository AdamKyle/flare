<?php

namespace Tests\Feature\Admin\LocationGems;

use App\Flare\Models\GameLocationGemParamters;
use App\Flare\Values\LocationType;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamters;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GameLocationGemParamtersControllerTest extends TestCase
{
    use CreateGameLocationGemParamters, CreateGameMap, CreateGameSkill, CreateItem, CreateLocation, CreateRole, CreateUser, RefreshDatabase;

    public function testAdminCanNavigateFromListToCreatePage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createLocation(['game_map_id' => $gameMap->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.list')
            ->see('Location Gems')
            ->click('Create Location Gem')
            ->seeRouteIs('admin.location-gems.create')
            ->see('Create Location Gem Parameters')
            ->dontSee('Character Power Reduction Range');
    }

    public function testAdminCanCreateLocationGemParamtersThroughForm(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $this->createItem(['type' => 'quest', 'drop_location_id' => $location->id]);
        $craftingSkill = $this->createGameSkill([
            'name' => 'Crafting',
            'can_train' => false,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.list')
            ->click('Create Location Gem')
            ->submitForm('Save', [
                'name' => 'Location Gem Parameters',
                'location_id' => $location->id,
                'description' => 'Parameters for location gems.',
                'monster_atonement' => GemTypeValue::FIRE,
                'monster_atonement_range' => '0.01-1.0',
                'character_xp_bonus_range' => '0.01-1.0',
                'crafting_skill_ids' => [$craftingSkill->id],
                'crafting_skill_bonus_range' => '0.01-1.0',
                'gold_gain_range' => '0.01-1.0',
                'unique_item_drop_chance_increase_range' => '0.01-1.0',
                'mythic_item_drop_chance_increase_range' => '0.01-1.0',
                'cosmic_item_drop_chance_increase_range' => '0.01-1.0',
                'ascended_item_drop_chance_increase_range' => '0.01-1.0',
            ]);

        $gameLocationGemParamters = GameLocationGemParamters::where('location_id', $location->id)->first();

        $this->assertNotNull($gameLocationGemParamters);
        $this->assertSame([$craftingSkill->id], $gameLocationGemParamters->crafting_skill_ids);
        $this->seeRouteIs('admin.location-gems.show', [
            'gameLocationGemParamters' => $gameLocationGemParamters,
        ])->see('Parameters for location gems.');
        $this->assertSame('Parameters for location gems.', $gameLocationGemParamters->description);
        $this->assertSame('0.01-1.0', $gameLocationGemParamters->crafting_skill_bonus_range);
        $this->assertSame('0.01-1.0', $gameLocationGemParamters->unique_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameLocationGemParamters->mythic_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameLocationGemParamters->cosmic_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameLocationGemParamters->ascended_item_drop_chance_increase_range);
        $this->assertArrayNotHasKey('character_power_reduction_range', $gameLocationGemParamters->getAttributes());
    }

    public function testAdminCanUpdateLocationGemParamtersThroughForm(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameLocationGemParamters = $this->createGameLocationGemParamters();
        $craftingSkill = $this->createGameSkill([
            'name' => 'Smithing',
            'can_train' => false,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.show', [
                'gameLocationGemParamters' => $gameLocationGemParamters,
            ])
            ->click('Edit')
            ->seeRouteIs('admin.location-gems.edit', [
                'gameLocationGemParamters' => $gameLocationGemParamters,
            ])
            ->submitForm('Save', [
                'name' => 'Updated Location Parameters',
                'location_id' => $gameLocationGemParamters->location_id,
                'description' => 'Updated location gem description.',
                'monster_atonement' => GemTypeValue::ICE,
                'monster_atonement_range' => '0.01-1.0',
                'character_xp_bonus_range' => '0.01-1.0',
                'crafting_skill_ids' => [$craftingSkill->id],
                'crafting_skill_bonus_range' => '0.01-1.0',
                'gold_gain_range' => '0.01-1.0',
            ])
            ->seeRouteIs('admin.location-gems.show', [
                'gameLocationGemParamters' => $gameLocationGemParamters,
            ])
            ->see('Updated Location Parameters')
            ->see('Updated location gem description.');

        $gameLocationGemParamters->refresh();

        $this->assertSame([$craftingSkill->id], $gameLocationGemParamters->crafting_skill_ids);
        $this->assertSame('0.01-1.0', $gameLocationGemParamters->crafting_skill_bonus_range);
        $this->assertSame('Updated location gem description.', $gameLocationGemParamters->description);
    }

    public function testAdminCanNavigateBackFromLocationGemShowPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameLocationGemParamters = $this->createGameLocationGemParamters();

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.show', [
                'gameLocationGemParamters' => $gameLocationGemParamters,
            ])
            ->see(route('admin.location-gems.list'))
            ->click('Back')
            ->seeRouteIs('admin.location-gems.list');
    }

    public function testAdminCanNavigateBackFromLocationGemEditPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameLocationGemParamters = $this->createGameLocationGemParamters();

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.edit', [
                'gameLocationGemParamters' => $gameLocationGemParamters,
            ])
            ->see(route('admin.location-gems.show', [
                'gameLocationGemParamters' => $gameLocationGemParamters,
            ]))
            ->click('Back')
            ->seeRouteIs('admin.location-gems.show', [
                'gameLocationGemParamters' => $gameLocationGemParamters,
            ]);
    }

    public function testInvalidIntegerRangeShowsValidationError(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $this->createItem(['type' => 'quest', 'drop_location_id' => $location->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->submitForm('Save', [
                'name' => 'Invalid Range',
                'location_id' => $location->id,
                'gold_gain_range' => '1-10',
            ])
            ->seeRouteIs('admin.location-gems.create')
            ->see('The gold gain range format is invalid.');

        $this->assertSame(0, GameLocationGemParamters::where('location_id', $location->id)->count());
    }

    public function testDuplicateLocationShowsValidationError(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameLocationGemParamters = $this->createGameLocationGemParamters();

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->submitForm('Save', [
                'name' => 'Duplicate Owner',
                'location_id' => $gameLocationGemParamters->location_id,
            ])
            ->seeRouteIs('admin.location-gems.create')
            ->see('The location id has already been taken.');
    }

    public function testCreatePageDropdownShowsLocationWithQuestItemDropAndMapName(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Test Map']);
        $location = $this->createLocation(['name' => 'Quest Town', 'game_map_id' => $gameMap->id]);
        $this->createItem(['type' => 'quest', 'drop_location_id' => $location->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->see('Quest Town (Test Map)');
    }

    public function testCreatePageDropdownDoesNotShowLocationWithoutQuestItemDrop(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createLocation(['name' => 'Ineligible Town', 'game_map_id' => $gameMap->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->dontSee('Ineligible Town');
    }

    public function testCreateFormFailsValidationWithIneligibleLocation(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $ineligibleLocation = $this->createLocation(['game_map_id' => $gameMap->id]);

        $this->actingAs($admin)
            ->post(route('admin.location-gems.store'), [
                'id' => 0,
                'name' => 'Test Gem Params',
                'location_id' => $ineligibleLocation->id,
            ])
            ->assertSessionHasErrors('location_id');
    }

    public function testEditPageDropdownShowsEligibleLocationsAcrossMultipleMaps(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $firstMap = $this->createGameMap(['name' => 'First Map']);
        $secondMap = $this->createGameMap(['name' => 'Second Map']);
        $firstLocation = $this->createLocation(['name' => 'Alpha Town', 'game_map_id' => $firstMap->id]);
        $secondLocation = $this->createLocation(['name' => 'Beta Town', 'game_map_id' => $secondMap->id]);
        $this->createItem(['type' => 'quest', 'drop_location_id' => $firstLocation->id]);
        $this->createItem(['type' => 'quest', 'drop_location_id' => $secondLocation->id]);
        $gameLocationGemParamters = $this->createGameLocationGemParamters(['location_id' => $firstLocation->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.edit', ['gameLocationGemParamters' => $gameLocationGemParamters])
            ->see('Alpha Town (First Map)')
            ->see('Beta Town (Second Map)');
    }

    public function testCreatePageDropdownShowsSpecialLocation(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->createLocation([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'type' => LocationType::GOLD_MINES,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->see('Gold Mine');
    }

    public function testSpecialLocationDropdownLabelIncludesSpecialType(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->createLocation([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'type' => LocationType::GOLD_MINES,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->see('Gold Mine [Special Type: Gold Mines] (Surface)');
    }

    public function testCreateFormCanSaveUsingSpecialLocation(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $specialLocation = $this->createLocation([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'type' => LocationType::GOLD_MINES,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->submitForm('Save', [
                'name' => 'Special Gem Params',
                'location_id' => $specialLocation->id,
            ]);

        $this->assertNotNull(GameLocationGemParamters::where('location_id', $specialLocation->id)->first());
    }

    public function testEditPageDropdownShowsSpecialLocationsAcrossMultipleMaps(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $surfaceMap = $this->createGameMap(['name' => 'Surface']);
        $purgatoryMap = $this->createGameMap(['name' => 'Purgatory']);
        $surfaceSpecial = $this->createLocation([
            'name' => 'Gold Mine',
            'game_map_id' => $surfaceMap->id,
            'type' => LocationType::GOLD_MINES,
        ]);
        $this->createLocation([
            'name' => 'Purgatory Smith',
            'game_map_id' => $purgatoryMap->id,
            'type' => LocationType::PURGATORY_SMITH_HOUSE,
        ]);
        $gameLocationGemParamters = $this->createGameLocationGemParamters(['location_id' => $surfaceSpecial->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.edit', ['gameLocationGemParamters' => $gameLocationGemParamters])
            ->see('Gold Mine [Special Type: Gold Mines] (Surface)')
            ->see('Purgatory Smith [Special Type: Purgatory Smiths House] (Purgatory)');
    }

    public function testRegularEligibleLocationsAppearBeforeSpecialLocations(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->createLocation([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'type' => LocationType::GOLD_MINES,
        ]);
        $regularLocation = $this->createLocation(['name' => 'Quest Town', 'game_map_id' => $gameMap->id]);
        $this->createItem(['type' => 'quest', 'drop_location_id' => $regularLocation->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->see('Quest Town')
            ->see('Gold Mine');

        $html = $this->response->getContent();
        $this->assertLessThan(strpos($html, 'Gold Mine'), strpos($html, 'Quest Town'));
    }

    public function testLocationsAreOrderedByPlaneOrder(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $hellMap = $this->createGameMap(['name' => 'Hell']);
        $surfaceMap = $this->createGameMap(['name' => 'Surface']);
        $hellLocation = $this->createLocation(['name' => 'Hell Quest Town', 'game_map_id' => $hellMap->id]);
        $surfaceLocation = $this->createLocation(['name' => 'Surface Quest Town', 'game_map_id' => $surfaceMap->id]);
        $this->createItem(['type' => 'quest', 'drop_location_id' => $hellLocation->id]);
        $this->createItem(['type' => 'quest', 'drop_location_id' => $surfaceLocation->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->see('Surface Quest Town')
            ->see('Hell Quest Town');

        $html = $this->response->getContent();
        $this->assertLessThan(strpos($html, 'Hell Quest Town'), strpos($html, 'Surface Quest Town'));
    }

    public function testSpecialLocationsFollowPlaneOrderWithinSpecialGroup(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $hellMap = $this->createGameMap(['name' => 'Hell']);
        $surfaceMap = $this->createGameMap(['name' => 'Surface']);
        $this->createLocation([
            'name' => 'Hell Special',
            'game_map_id' => $hellMap->id,
            'type' => LocationType::BROKEN_ANVIL,
        ]);
        $this->createLocation([
            'name' => 'Surface Special',
            'game_map_id' => $surfaceMap->id,
            'type' => LocationType::GOLD_MINES,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->see('Surface Special')
            ->see('Hell Special');

        $html = $this->response->getContent();
        $this->assertLessThan(strpos($html, 'Hell Special'), strpos($html, 'Surface Special'));
    }
}
