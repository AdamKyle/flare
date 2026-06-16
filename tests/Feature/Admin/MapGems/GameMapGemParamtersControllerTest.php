<?php

namespace Tests\Feature\Admin\MapGems;

use App\Flare\Models\GameMapGemParamters;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamters;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GameMapGemParamtersControllerTest extends TestCase
{
    use CreateGameMap, CreateGameMapGemParamters, CreateGameSkill, CreateRole, CreateUser, RefreshDatabase;

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

        $gameMapGemParamters = GameMapGemParamters::where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($gameMapGemParamters);
        $this->assertSame([$craftingSkill->id], $gameMapGemParamters->crafting_skill_ids);
        $this->seeRouteIs('admin.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ])->see('Parameters for surface map gems.');
        $this->assertSame('Parameters for surface map gems.', $gameMapGemParamters->description);
        $this->assertSame('0.01-1.0', $gameMapGemParamters->crafting_skill_bonus_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamters->unique_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamters->mythic_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamters->cosmic_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamters->ascended_item_drop_chance_increase_range);
        $this->assertSame('0.01-1.0', $gameMapGemParamters->character_power_reduction_range);
    }

    public function testAdminCanUpdateMapGemParamtersThroughForm(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMapGemParamters = $this->createGameMapGemParamters();
        $craftingSkill = $this->createGameSkill([
            'name' => 'Smithing',
            'can_train' => false,
        ]);

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.show', [
                'gameMapGemParamters' => $gameMapGemParamters,
            ])
            ->click('Edit')
            ->seeRouteIs('admin.map-gems.edit', [
                'gameMapGemParamters' => $gameMapGemParamters,
            ])
            ->submitForm('Save', [
                'name' => 'Updated Map Parameters',
                'game_map_id' => $gameMapGemParamters->game_map_id,
                'description' => 'Updated map gem description.',
                'monster_atonement' => GemTypeValue::ICE,
                'monster_atonement_range' => '0.01-1.0',
                'character_xp_bonus_range' => '0.01-1.0',
                'crafting_skill_ids' => [$craftingSkill->id],
                'crafting_skill_bonus_range' => '0.01-1.0',
                'gold_gain_range' => '0.01-1.0',
            ])
            ->seeRouteIs('admin.map-gems.show', [
                'gameMapGemParamters' => $gameMapGemParamters,
            ])
            ->see('Updated Map Parameters')
            ->see('Updated map gem description.');

        $gameMapGemParamters->refresh();

        $this->assertSame([$craftingSkill->id], $gameMapGemParamters->crafting_skill_ids);
        $this->assertSame('0.01-1.0', $gameMapGemParamters->crafting_skill_bonus_range);
        $this->assertSame('Updated map gem description.', $gameMapGemParamters->description);
    }

    public function testAdminCanNavigateBackFromMapGemShowPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMapGemParamters = $this->createGameMapGemParamters();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.show', [
                'gameMapGemParamters' => $gameMapGemParamters,
            ])
            ->see(route('admin.map-gems.list'))
            ->click('Back')
            ->seeRouteIs('admin.map-gems.list');
    }

    public function testAdminCanNavigateBackFromMapGemEditPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMapGemParamters = $this->createGameMapGemParamters();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.edit', [
                'gameMapGemParamters' => $gameMapGemParamters,
            ])
            ->see(route('admin.map-gems.show', [
                'gameMapGemParamters' => $gameMapGemParamters,
            ]))
            ->click('Back')
            ->seeRouteIs('admin.map-gems.show', [
                'gameMapGemParamters' => $gameMapGemParamters,
            ]);
    }

    public function testInvalidIntegerRangeShowsValidationError(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.create')
            ->submitForm('Save', [
                'name' => 'Invalid Range',
                'game_map_id' => $gameMap->id,
                'gold_gain_range' => '1-10',
            ])
            ->seeRouteIs('admin.map-gems.create')
            ->see('The gold gain range format is invalid.');

        $this->assertSame(0, GameMapGemParamters::where('game_map_id', $gameMap->id)->count());
    }

    public function testDuplicateGameMapShowsValidationError(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMapGemParamters = $this->createGameMapGemParamters();

        $this->actingAs($admin)
            ->visitRoute('admin.map-gems.create')
            ->submitForm('Save', [
                'name' => 'Duplicate Owner',
                'game_map_id' => $gameMapGemParamters->game_map_id,
            ])
            ->seeRouteIs('admin.map-gems.create')
            ->see('The game map id has already been taken.');
    }
}
