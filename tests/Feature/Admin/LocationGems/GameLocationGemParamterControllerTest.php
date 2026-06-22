<?php

namespace Tests\Feature\Admin\LocationGems;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\Gem;
use App\Flare\Models\GemBagSlot;
use App\Flare\Values\LocationType;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GameLocationGemParamterControllerTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameSkill, CreateItem, CreateLocation, CreateRole, CreateUser, RefreshDatabase;

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

        $gameLocationGemParamter = GameLocationGemParamter::where('location_id', $location->id)->first();

        $this->assertNotNull($gameLocationGemParamter);
        $this->assertSame([$craftingSkill->id], $gameLocationGemParamter->crafting_skill_ids);
        $this->seeRouteIs('admin.location-gems.show', [
            'gameLocationGemParamter' => $gameLocationGemParamter,
        ])->see('Parameters for location gems.');
        $this->assertSame('Parameters for location gems.', $gameLocationGemParamter->description);
        $this->assertSame('0.01-1.0', $gameLocationGemParamter->crafting_skill_bonus_range);
        $this->assertSame('0.01-1.0', $gameLocationGemParamter->unique_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameLocationGemParamter->mythic_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameLocationGemParamter->cosmic_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameLocationGemParamter->ascended_item_drop_chance_increase_range);
        $this->assertArrayNotHasKey('character_power_reduction_range', $gameLocationGemParamter->getAttributes());
    }

    public function testAdminCanUpdateLocationGemParamtersThroughForm(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameLocationGemParamter = $this->createGameLocationGemParamter();
        $craftingSkill = $this->createGameSkill([
            'name' => 'Smithing',
            'can_train' => false,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.show', [
                'gameLocationGemParamter' => $gameLocationGemParamter,
            ])
            ->click('Edit')
            ->seeRouteIs('admin.location-gems.edit', [
                'gameLocationGemParamter' => $gameLocationGemParamter,
            ])
            ->submitForm('Save', [
                'name' => 'Updated Location Parameters',
                'location_id' => $gameLocationGemParamter->location_id,
                'description' => 'Updated location gem description.',
                'monster_atonement' => GemTypeValue::ICE,
                'monster_atonement_range' => '0.01-1.0',
                'character_xp_bonus_range' => '0.01-1.0',
                'crafting_skill_ids' => [$craftingSkill->id],
                'crafting_skill_bonus_range' => '0.01-1.0',
                'gold_gain_range' => '0.01-1.0',
            ])
            ->seeRouteIs('admin.location-gems.show', [
                'gameLocationGemParamter' => $gameLocationGemParamter,
            ])
            ->see('Updated Location Parameters')
            ->see('Updated location gem description.');

        $gameLocationGemParamter->refresh();

        $this->assertSame([$craftingSkill->id], $gameLocationGemParamter->crafting_skill_ids);
        $this->assertSame('0.01-1.0', $gameLocationGemParamter->crafting_skill_bonus_range);
        $this->assertSame('Updated location gem description.', $gameLocationGemParamter->description);
    }

    public function testAdminCanNavigateBackFromLocationGemShowPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameLocationGemParamter = $this->createGameLocationGemParamter();

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.show', [
                'gameLocationGemParamter' => $gameLocationGemParamter,
            ])
            ->see(route('admin.location-gems.list'))
            ->click('Back')
            ->seeRouteIs('admin.location-gems.list');
    }

    public function testAdminCanNavigateBackFromLocationGemEditPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameLocationGemParamter = $this->createGameLocationGemParamter();

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.edit', [
                'gameLocationGemParamter' => $gameLocationGemParamter,
            ])
            ->see(route('admin.location-gems.show', [
                'gameLocationGemParamter' => $gameLocationGemParamter,
            ]))
            ->click('Back')
            ->seeRouteIs('admin.location-gems.show', [
                'gameLocationGemParamter' => $gameLocationGemParamter,
            ]);
    }

    public function testIntegerAndReversedRangesAreAccepted(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $this->createItem(['type' => 'quest', 'drop_location_id' => $location->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->submitForm('Save', [
                'name' => 'Valid Range',
                'location_id' => $location->id,
                'gold_gain_range' => '0.3-0.08',
            ]);

        $profile = GameLocationGemParamter::where('location_id', $location->id)->firstOrFail();
        $this->seeRouteIs('admin.location-gems.show', ['gameLocationGemParamter' => $profile]);
        $this->assertSame('0.3-0.08', $profile->gold_gain_range);
    }

    public function testNonNumericRangeShowsValidationError(): void
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
                'gold_gain_range' => 'one-three',
            ])
            ->seeRouteIs('admin.location-gems.create')
            ->see('The range must contain two numeric values separated by a hyphen.');
    }

    public function testDuplicateLocationShowsValidationError(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameLocationGemParamter = $this->createGameLocationGemParamter();

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.create')
            ->submitForm('Save', [
                'name' => 'Duplicate Owner',
                'location_id' => $gameLocationGemParamter->location_id,
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
        $gameLocationGemParamter = $this->createGameLocationGemParamter(['location_id' => $firstLocation->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.edit', ['gameLocationGemParamter' => $gameLocationGemParamter])
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

        $this->assertNotNull(GameLocationGemParamter::where('location_id', $specialLocation->id)->first());
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
        $gameLocationGemParamter = $this->createGameLocationGemParamter(['location_id' => $surfaceSpecial->id]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.edit', ['gameLocationGemParamter' => $gameLocationGemParamter])
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

    public function testAdminShowUsesRolledGemPointerForRollButton(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter();
        Gem::factory()->locationGenerated($profile)->create([
            'name' => 'Historical Location Gem',
            'domain' => Gem::DOMAIN_LOCATION,
            'game_location_gem_paramters_id' => $profile->id,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.show', ['gameLocationGemParamter' => $profile])
            ->see('Roll Gem')
            ->dontSee('Re-roll Gem')
            ->dontSee('View Rolled Stats')
            ->dontSee('Historical Location Gem');
    }

    public function testAdminCanRollAndRerollLocationGem(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter([
            'name' => 'Location Profile',
            'character_xp_bonus_range' => '0.1000-0.2000',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.location-gems.roll', ['gameLocationGemParamter' => $profile]))
            ->response;

        $response->assertRedirect(route('admin.location-gems.show', ['gameLocationGemParamter' => $profile]));

        $profile->refresh();
        $firstGem = $profile->rolledGem;

        $this->assertSame(1, Gem::count());
        $this->assertSame(Gem::DOMAIN_LOCATION, $firstGem->domain);
        $this->assertSame($profile->name, $firstGem->name);
        $this->assertSame($profile->id, $firstGem->game_location_gem_paramters_id);
        $this->assertSame($admin->id, $firstGem->rolled_by_user_id);
        $this->assertSame(1, $firstGem->roll_number);
        $this->assertSame(1, $profile->roll_count);
        $this->assertGreaterThanOrEqual(0.1, $firstGem->character_xp_bonus);
        $this->assertLessThanOrEqual(0.2, $firstGem->character_xp_bonus);
        $this->assertNull($firstGem->character_power_reduction);
        $this->assertSame(0, GemBagSlot::count());

        $this->visitRoute('admin.location-gems.show', ['gameLocationGemParamter' => $profile])
            ->see('Re-roll Gem')
            ->see('View Rolled Stats')
            ->dontSee('Current Rolled Gem')
            ->dontSee('Gem Roll Number');

        $response = $this->post(route('admin.location-gems.roll', ['gameLocationGemParamter' => $profile]))
            ->response;

        $response->assertRedirect(route('admin.location-gems.show', ['gameLocationGemParamter' => $profile]));

        $profile->refresh();
        $secondGem = $profile->rolledGem;

        $this->assertSame(2, Gem::count());
        $this->assertNotSame($firstGem->id, $secondGem->id);
        $this->assertNotNull(Gem::find($firstGem->id));
        $this->assertSame($secondGem->id, $profile->rolled_gem_id);
        $this->assertSame(2, $profile->roll_count);
        $this->assertSame(2, $secondGem->roll_number);
        $this->assertSame($profile->name, $secondGem->name);
        $this->assertNull($secondGem->character_power_reduction);
        $this->assertSame(0, GemBagSlot::count());
    }

    public function testAdminCanViewCurrentLocationRolledStats(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Location Rolled Profile']);
        $rolledGem = Gem::factory()->locationGenerated($profile)->create([
            'name' => $profile->name,
            'domain' => Gem::DOMAIN_LOCATION,
            'game_location_gem_paramters_id' => $profile->id,
            'rolled_by_user_id' => $admin->id,
            'roll_number' => 4,
            'character_xp_bonus' => 0.25,
            'gold_gain' => 0.5,
            'gold_dust_gain' => 0,
            'enemy_strength_increase' => 0.75,
            'enemy_healing_increase' => 0,
            'character_power_reduction' => 0.125,
            'monster_atonement_amount' => 0,
        ]);
        $profile->update([
            'rolled_gem_id' => $rolledGem->id,
            'roll_count' => 4,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.show', ['gameLocationGemParamter' => $profile])
            ->click('View Rolled Stats')
            ->seeRouteIs('admin.location-gems.rolled', ['gameLocationGemParamter' => $profile])
            ->see('Location Rolled Profile')
            ->see('Gem Roll Number')
            ->see('>4</dd>')
            ->see('Character XP Bonus')
            ->see('+25.000%')
            ->see('Gold Gain')
            ->see('+50.000%')
            ->see('Enemy Strength Increase')
            ->see('+75.000%')
            ->see('text-green-700 dark:text-green-400')
            ->see('text-red-700 dark:text-red-400')
            ->dontSee('Rolled By User ID')
            ->dontSee('+0.25%')
            ->dontSee('Gold Dust Gain')
            ->dontSee('Enemy Healing Increase')
            ->dontSee('Monster Atonement Amount')
            ->dontSee('Character Power Reduction')
            ->see(route('admin.location-gems.show', ['gameLocationGemParamter' => $profile]));
    }

    public function testLocationRolledGemPageFormatsPercentagesToThreeDecimalPlaces(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Decimal Format Location Profile']);
        $rolledGem = Gem::factory()->locationGenerated($profile)->create([
            'name' => $profile->name,
            'domain' => Gem::DOMAIN_LOCATION,
            'game_location_gem_paramters_id' => $profile->id,
            'rolled_by_user_id' => $admin->id,
            'roll_number' => 1,
            'character_xp_bonus' => 0.12345,
            'gold_gain' => 0,
            'enemy_strength_increase' => 0,
            'character_power_reduction' => 0,
            'monster_atonement_amount' => 0,
        ]);
        $profile->update(['rolled_gem_id' => $rolledGem->id, 'roll_count' => 1]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.rolled', ['gameLocationGemParamter' => $profile])
            ->see('+12.345%')
            ->dontSee('+0.12345%')
            ->dontSee('+12.34500%');
    }

    public function testLocationRolledGemPageDoesNotShowRolledByUserId(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'No User ID Location Profile']);
        $rolledGem = Gem::factory()->locationGenerated($profile)->create([
            'name' => $profile->name,
            'domain' => Gem::DOMAIN_LOCATION,
            'game_location_gem_paramters_id' => $profile->id,
            'rolled_by_user_id' => $admin->id,
            'roll_number' => 1,
            'monster_atonement_amount' => 0,
        ]);
        $profile->update(['rolled_gem_id' => $rolledGem->id, 'roll_count' => 1]);

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.rolled', ['gameLocationGemParamter' => $profile])
            ->dontSee('Rolled By User ID');
    }

    public function testLocationRolledStatsRedirectsWhenNoCurrentGemExists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter();

        $this->actingAs($admin)
            ->visitRoute('admin.location-gems.rolled', ['gameLocationGemParamter' => $profile])
            ->seeRouteIs('admin.location-gems.show', ['gameLocationGemParamter' => $profile])
            ->see('No rolled gem is available for this location gem profile.');
    }

    public function testPublicLocationGemPageDoesNotExposeRollingControlsOrRolledValues(): void
    {
        $profile = $this->createGameLocationGemParamter();
        $rolledGem = Gem::factory()->locationGenerated($profile)->create([
            'name' => 'Private Location Roll Value',
            'domain' => Gem::DOMAIN_LOCATION,
            'game_location_gem_paramters_id' => $profile->id,
            'character_xp_bonus' => 0.1234,
        ]);
        $profile->update([
            'rolled_gem_id' => $rolledGem->id,
            'roll_count' => 1,
        ]);

        $this->visitRoute('info.page.location-gems.show', ['gameLocationGemParamter' => $profile])
            ->dontSee('Roll Gem')
            ->dontSee('Re-roll Gem')
            ->dontSee('View Rolled Stats')
            ->dontSee('Private Location Roll Value')
            ->dontSee('Current Rolled Gem');
    }

    public function testNonAdminCannotRollLocationGem(): void
    {
        $user = $this->createUser();
        $profile = $this->createGameLocationGemParamter();

        $response = $this->actingAs($user)
            ->post(route('admin.location-gems.roll', ['gameLocationGemParamter' => $profile]))
            ->response;

        $response->assertRedirect();

        $this->assertSame(0, Gem::count());
        $this->assertNull($profile->fresh()->rolled_gem_id);
    }

    public function testNonAdminCannotViewLocationRolledStats(): void
    {
        $user = $this->createUser();
        $profile = $this->createGameLocationGemParamter();

        $response = $this->actingAs($user)
            ->call('GET', route('admin.location-gems.rolled', ['gameLocationGemParamter' => $profile]));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You don\'t have permission to view that.');
    }
}
