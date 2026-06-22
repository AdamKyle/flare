<?php

namespace Tests\Feature\Admin\MapGems;

use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Gem;
use App\Flare\Models\GemBagSlot;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GameMapGemParamterControllerTest extends TestCase
{
    use CreateGameMap, CreateGameMapGemParamter, CreateGameSkill, CreateRole, CreateUser, RefreshDatabase;

    public function testAdminCanNavigateFromListToCreatePage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.list')
            ->see('Map Gems')
            ->click('Create Map Gem')
            ->seeRouteIs('admin.map-gems.create')
            ->see('Create Map Gem Parameters');
    }

    public function testAdminCanCreateMapGemParamtersThroughForm(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $craftingSkill = $this->createGameSkill([
            'name' => 'Crafting',
            'can_train' => false,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.list')
            ->click('Create Map Gem')
            ->submitForm('Save', [
                'name' => 'Surface Gem Parameters',
                'game_map_id' => $gameMap->id,
                'description' => 'Parameters for surface map gems.',
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
                'character_power_reduction_range' => '0.01-1.0',
            ]);

        $gameMapGemParamter = GameMapGemParamter::where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($gameMapGemParamter);
        $this->assertSame([$craftingSkill->id], $gameMapGemParamter->crafting_skill_ids);
        $this->seeRouteIs('admin.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ])->see('Parameters for surface map gems.');
        $this->assertSame('Parameters for surface map gems.', $gameMapGemParamter->description);
        $this->assertSame('0.01-1.0', $gameMapGemParamter->crafting_skill_bonus_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamter->unique_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamter->mythic_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamter->cosmic_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamter->ascended_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamter->character_power_reduction_range);
    }

    public function testAdminCanUpdateMapGemParamtersThroughForm(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMapGemParamter = $this->createGameMapGemParamter();
        $craftingSkill = $this->createGameSkill([
            'name' => 'Smithing',
            'can_train' => false,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.show', [
                'gameMapGemParamter' => $gameMapGemParamter,
            ])
            ->click('Edit')
            ->seeRouteIs('admin.map-gems.edit', [
                'gameMapGemParamter' => $gameMapGemParamter,
            ])
            ->submitForm('Save', [
                'name' => 'Updated Map Parameters',
                'game_map_id' => $gameMapGemParamter->game_map_id,
                'description' => 'Updated map gem description.',
                'monster_atonement' => GemTypeValue::ICE,
                'monster_atonement_range' => '0.01-1.0',
                'character_xp_bonus_range' => '0.01-1.0',
                'crafting_skill_ids' => [$craftingSkill->id],
                'crafting_skill_bonus_range' => '0.01-1.0',
                'gold_gain_range' => '0.01-1.0',
            ])
            ->seeRouteIs('admin.map-gems.show', [
                'gameMapGemParamter' => $gameMapGemParamter,
            ])
            ->see('Updated Map Parameters')
            ->see('Updated map gem description.');

        $gameMapGemParamter->refresh();

        $this->assertSame([$craftingSkill->id], $gameMapGemParamter->crafting_skill_ids);
        $this->assertSame('0.01-1.0', $gameMapGemParamter->crafting_skill_bonus_range);
        $this->assertSame('Updated map gem description.', $gameMapGemParamter->description);
    }

    public function testAdminCanNavigateBackFromMapGemShowPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMapGemParamter = $this->createGameMapGemParamter();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.show', [
                'gameMapGemParamter' => $gameMapGemParamter,
            ])
            ->see(route('admin.map-gems.list'))
            ->click('Back')
            ->seeRouteIs('admin.map-gems.list');
    }

    public function testAdminCanNavigateBackFromMapGemEditPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMapGemParamter = $this->createGameMapGemParamter();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.edit', [
                'gameMapGemParamter' => $gameMapGemParamter,
            ])
            ->see(route('admin.map-gems.show', [
                'gameMapGemParamter' => $gameMapGemParamter,
            ]))
            ->click('Back')
            ->seeRouteIs('admin.map-gems.show', [
                'gameMapGemParamter' => $gameMapGemParamter,
            ]);
    }

    public function testIntegerAndReversedRangesAreAccepted(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.create')
            ->submitForm('Save', [
                'name' => 'Valid Range',
                'game_map_id' => $gameMap->id,
                'gold_gain_range' => '1-3',
                'character_power_reduction_range' => '0.05-0.012',
            ]);

        $profile = GameMapGemParamter::where('game_map_id', $gameMap->id)->firstOrFail();
        $this->seeRouteIs('admin.map-gems.show', ['gameMapGemParamter' => $profile]);
        $this->assertSame('1-3', $profile->gold_gain_range);
        $this->assertSame('0.05-0.012', $profile->character_power_reduction_range);
    }

    public function testNonNumericRangeShowsValidationError(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.create')
            ->submitForm('Save', [
                'name' => 'Invalid Range',
                'game_map_id' => $gameMap->id,
                'gold_gain_range' => 'one-three',
            ])
            ->seeRouteIs('admin.map-gems.create')
            ->see('The range must contain two numeric values separated by a hyphen.');
    }

    public function testDuplicateGameMapShowsValidationError(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMapGemParamter = $this->createGameMapGemParamter();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.create')
            ->submitForm('Save', [
                'name' => 'Duplicate Owner',
                'game_map_id' => $gameMapGemParamter->game_map_id,
            ])
            ->seeRouteIs('admin.map-gems.create')
            ->see('The game map id has already been taken.');
    }

    public function testAdminShowUsesRolledGemPointerForRollButton(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter();
        Gem::factory()->mapGenerated($profile)->create([
            'name' => 'Historical Map Gem',
            'domain' => Gem::DOMAIN_MAP,
            'game_map_gem_paramters_id' => $profile->id,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.show', ['gameMapGemParamter' => $profile])
            ->see('Roll Gem')
            ->dontSee('Re-roll Gem')
            ->dontSee('View Rolled Stats')
            ->dontSee('Historical Map Gem');
    }

    public function testAdminCanRollAndRerollMapGem(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter([
            'name' => 'Surface Profile',
            'character_xp_bonus_range' => '0.1000-0.2000',
            'character_power_reduction_range' => '0.3000-0.4000',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.map-gems.roll', ['gameMapGemParamter' => $profile]))
            ->response;

        $response->assertRedirect(route('admin.map-gems.show', ['gameMapGemParamter' => $profile]));

        $profile->refresh();
        $firstGem = $profile->rolledGem;

        $this->assertSame(1, Gem::count());
        $this->assertSame(Gem::DOMAIN_MAP, $firstGem->domain);
        $this->assertSame($profile->name, $firstGem->name);
        $this->assertSame($profile->id, $firstGem->game_map_gem_paramters_id);
        $this->assertSame($admin->id, $firstGem->rolled_by_user_id);
        $this->assertSame(1, $firstGem->roll_number);
        $this->assertSame(1, $profile->roll_count);
        $this->assertGreaterThanOrEqual(0.1, $firstGem->character_xp_bonus);
        $this->assertLessThanOrEqual(0.2, $firstGem->character_xp_bonus);
        $this->assertGreaterThanOrEqual(0.3, $firstGem->character_power_reduction);
        $this->assertLessThanOrEqual(0.4, $firstGem->character_power_reduction);
        $this->assertSame(0, GemBagSlot::count());

        $this->visitRoute('admin.map-gems.show', ['gameMapGemParamter' => $profile])
            ->see('Re-roll Gem')
            ->see('View Rolled Stats')
            ->dontSee('Current Rolled Gem')
            ->dontSee('Gem Roll Number');

        $response = $this->post(route('admin.map-gems.roll', ['gameMapGemParamter' => $profile]))
            ->response;

        $response->assertRedirect(route('admin.map-gems.show', ['gameMapGemParamter' => $profile]));

        $profile->refresh();
        $secondGem = $profile->rolledGem;

        $this->assertSame(2, Gem::count());
        $this->assertNotSame($firstGem->id, $secondGem->id);
        $this->assertNotNull(Gem::find($firstGem->id));
        $this->assertSame($secondGem->id, $profile->rolled_gem_id);
        $this->assertSame(2, $profile->roll_count);
        $this->assertSame(2, $secondGem->roll_number);
        $this->assertSame($profile->name, $secondGem->name);
        $this->assertSame(0, GemBagSlot::count());
    }

    public function testAdminCanViewCurrentMapRolledStats(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Map Rolled Profile']);
        $rolledGem = Gem::factory()->mapGenerated($profile)->create([
            'name' => $profile->name,
            'domain' => Gem::DOMAIN_MAP,
            'game_map_gem_paramters_id' => $profile->id,
            'rolled_by_user_id' => $admin->id,
            'roll_number' => 3,
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
            'roll_count' => 3,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.show', ['gameMapGemParamter' => $profile])
            ->click('View Rolled Stats')
            ->seeRouteIs('admin.map-gems.rolled', ['gameMapGemParamter' => $profile])
            ->see('Map Rolled Profile')
            ->see('Gem Roll Number')
            ->see('>3</dd>')
            ->see('Character XP Bonus')
            ->see('+25.000%')
            ->see('Gold Gain')
            ->see('+50.000%')
            ->see('Enemy Strength Increase')
            ->see('+75.000%')
            ->see('Character Power Reduction')
            ->see('-12.500%')
            ->see('text-green-700 dark:text-green-400')
            ->see('text-red-700 dark:text-red-400')
            ->dontSee('Rolled By User ID')
            ->dontSee('+0.25%')
            ->dontSee('+0.125%')
            ->dontSee('Gold Dust Gain')
            ->dontSee('Enemy Healing Increase')
            ->dontSee('Monster Atonement Amount')
            ->see(route('admin.map-gems.show', ['gameMapGemParamter' => $profile]));
    }

    public function testMapRolledGemPageFormatsPercentagesToThreeDecimalPlaces(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Decimal Format Profile']);
        $rolledGem = Gem::factory()->mapGenerated($profile)->create([
            'name' => $profile->name,
            'domain' => Gem::DOMAIN_MAP,
            'game_map_gem_paramters_id' => $profile->id,
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
            ->visitRoute('admin.map-gems.rolled', ['gameMapGemParamter' => $profile])
            ->see('+12.345%')
            ->dontSee('+0.12345%')
            ->dontSee('+12.34500%');
    }

    public function testMapRolledGemCharacterPowerReductionRendersAsNegativePercentage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Negative Power Profile']);
        $rolledGem = Gem::factory()->mapGenerated($profile)->create([
            'name' => $profile->name,
            'domain' => Gem::DOMAIN_MAP,
            'game_map_gem_paramters_id' => $profile->id,
            'rolled_by_user_id' => $admin->id,
            'roll_number' => 1,
            'character_xp_bonus' => 0,
            'gold_gain' => 0,
            'enemy_strength_increase' => 0,
            'character_power_reduction' => 0.05,
            'monster_atonement_amount' => 0,
        ]);
        $profile->update(['rolled_gem_id' => $rolledGem->id, 'roll_count' => 1]);

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.rolled', ['gameMapGemParamter' => $profile])
            ->see('-5.000%')
            ->see('text-red-700 dark:text-red-400')
            ->dontSee('+5.000%')
            ->dontSee('+0.05%');
    }

    public function testMapRolledGemPageDoesNotShowRolledByUserId(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'No User ID Profile']);
        $rolledGem = Gem::factory()->mapGenerated($profile)->create([
            'name' => $profile->name,
            'domain' => Gem::DOMAIN_MAP,
            'game_map_gem_paramters_id' => $profile->id,
            'rolled_by_user_id' => $admin->id,
            'roll_number' => 1,
            'monster_atonement_amount' => 0,
        ]);
        $profile->update(['rolled_gem_id' => $rolledGem->id, 'roll_count' => 1]);

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.rolled', ['gameMapGemParamter' => $profile])
            ->dontSee('Rolled By User ID');
    }

    public function testMapRolledStatsRedirectsWhenNoCurrentGemExists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.rolled', ['gameMapGemParamter' => $profile])
            ->seeRouteIs('admin.map-gems.show', ['gameMapGemParamter' => $profile])
            ->see('No rolled gem is available for this map gem profile.');
    }

    public function testPublicMapGemPageDoesNotExposeRollingControlsOrRolledValues(): void
    {
        $profile = $this->createGameMapGemParamter();
        $rolledGem = Gem::factory()->mapGenerated($profile)->create([
            'name' => 'Private Map Roll Value',
            'domain' => Gem::DOMAIN_MAP,
            'game_map_gem_paramters_id' => $profile->id,
            'character_xp_bonus' => 0.1234,
        ]);
        $profile->update([
            'rolled_gem_id' => $rolledGem->id,
            'roll_count' => 1,
        ]);

        $this->visitRoute('info.page.map-gems.show', ['gameMapGemParamter' => $profile])
            ->dontSee('Roll Gem')
            ->dontSee('Re-roll Gem')
            ->dontSee('View Rolled Stats')
            ->dontSee('Private Map Roll Value')
            ->dontSee('Current Rolled Gem');
    }

    public function testNonAdminCannotRollMapGem(): void
    {
        $user = $this->createUser();
        $profile = $this->createGameMapGemParamter();

        $response = $this->actingAs($user)
            ->post(route('admin.map-gems.roll', ['gameMapGemParamter' => $profile]))
            ->response;

        $response->assertRedirect();

        $this->assertSame(0, Gem::count());
        $this->assertNull($profile->fresh()->rolled_gem_id);
    }

    public function testNonAdminCannotViewMapRolledStats(): void
    {
        $user = $this->createUser();
        $profile = $this->createGameMapGemParamter();

        $response = $this->actingAs($user)
            ->call('GET', route('admin.map-gems.rolled', ['gameMapGemParamter' => $profile]));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You don\'t have permission to view that.');
    }
}
