<?php

namespace Tests\Feature\Http\Controllers;

use App\Flare\View\Livewire\Info\LocationGems;
use App\Flare\View\Livewire\Info\MapGems;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamters;
use Tests\Traits\CreateGameMapGemParamters;

class InfoGemParamtersControllerTest extends TestCase
{
    use CreateGameLocationGemParamters, CreateGameMapGemParamters, RefreshDatabase;

    public function testGuestCanViewMapGemsList(): void
    {
        $response = $this->call('GET', route('info.page.map-gems.list'));

        $response->assertOk();
        $response->assertSee('Map Gems');
    }

    public function testGuestCanViewMapGemShow(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters([
            'crafting_skill_bonus_range' => '0.25-0.75',
            'description' => 'Public map gem description.',
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertSee($gameMapGemParamters->name);
        $response->assertSee($gameMapGemParamters->gameMap->name);
        $response->assertSee('0.25-0.75');
        $response->assertSee('Public map gem description.');
        $response->assertSee('href="'.route('info.page.map-gems.list').'"', false);
    }

    public function testGuestCanViewLocationGemsList(): void
    {
        $response = $this->call('GET', route('info.page.location-gems.list'));

        $response->assertOk();
        $response->assertSee('Location Gems');
    }

    public function testGuestCanViewLocationGemShow(): void
    {
        $gameLocationGemParamters = $this->createGameLocationGemParamters([
            'crafting_skill_bonus_range' => '0.25-0.75',
            'description' => 'Public location gem description.',
        ]);

        $response = $this->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamters' => $gameLocationGemParamters,
        ]));

        $response->assertOk();
        $response->assertSee($gameLocationGemParamters->name);
        $response->assertSee($gameLocationGemParamters->location->name);
        $response->assertSee('0.25-0.75');
        $response->assertSee('Public location gem description.');
        $response->assertSee('href="'.route('info.page.location-gems.list').'"', false);
    }

    public function testNonAdminCanViewMapGemInformationWithoutEditLink(): void
    {
        $user = (new CharacterFactory)
            ->createBaseCharacter(assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter()
            ->user;
        $gameMapGemParamters = $this->createGameMapGemParamters();

        $response = $this->actingAs($user)->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertDontSee(route('admin.map-gems.edit', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]), false);
    }

    public function testNonAdminCanViewLocationGemInformationWithoutEditLink(): void
    {
        $user = (new CharacterFactory)
            ->createBaseCharacter(assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter()
            ->user;
        $gameLocationGemParamters = $this->createGameLocationGemParamters();

        $response = $this->actingAs($user)->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamters' => $gameLocationGemParamters,
        ]));

        $response->assertOk();
        $response->assertDontSee(route('admin.location-gems.edit', [
            'gameLocationGemParamters' => $gameLocationGemParamters,
        ]), false);
    }

    public function testMapGemShowDisplaysNotAvailableForEmptyDescription(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters([
            'description' => null,
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['Description', 'N/A']);
    }

    public function testLocationGemShowDisplaysNotAvailableForEmptyDescription(): void
    {
        $gameLocationGemParamters = $this->createGameLocationGemParamters([
            'description' => null,
        ]);

        $response = $this->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamters' => $gameLocationGemParamters,
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['Description', 'N/A']);
    }

    public function testMapGemsLivewireTableRendersWithRecord(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters();

        Livewire::test(MapGems::class)
            ->assertSee($gameMapGemParamters->name)
            ->assertSee(route('info.page.map-gems.show', [
                'gameMapGemParamters' => $gameMapGemParamters,
            ]), false);
    }

    public function testLocationGemsLivewireTableRendersWithRecord(): void
    {
        $gameLocationGemParamters = $this->createGameLocationGemParamters();

        Livewire::test(LocationGems::class)
            ->assertSee($gameLocationGemParamters->name)
            ->assertSee(route('info.page.location-gems.show', [
                'gameLocationGemParamters' => $gameLocationGemParamters,
            ]), false);
    }

    public function testMapGemShowFormatsBeneficialAndHarmfulRanges(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters([
            'description' => 'Formatting test description.',
            'character_xp_bonus_range' => '0.05-0.10',
            'enemy_strength_increase_range' => '0.05-0.10',
            'character_power_reduction_range' => '0.05-0.10',
            'monster_gold_drop_increase_range' => '0.05-0.10',
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertSee('+0.05-0.10%');
        $response->assertSee('text-green-700 dark:text-green-400', false);
        $response->assertSee('text-red-700 dark:text-red-400', false);
        $response->assertSeeInOrder(['Description', 'Formatting test description.', 'Overview']);
        $response->assertSee('What this setup controls');
        $response->assertSee('Player reward modifiers');
        $response->assertSee('any map-only character power reduction.');
        $response->assertSee('border-blue-300 bg-blue-50', false);
        $this->assertSame(1, substr_count($response->getContent(), '>Description</h3>'));
    }

    public function testLocationGemShowFormatsBeneficialAndHarmfulRanges(): void
    {
        $gameLocationGemParamters = $this->createGameLocationGemParamters([
            'description' => 'Location formatting description.',
            'unique_item_drop_chance_increase_range' => '0.05-0.10',
            'enemy_counter_chance_range' => '0.05-0.10',
            'enemy_quest_item_drop_chance_increase_range' => '0.05-0.10',
        ]);

        $response = $this->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamters' => $gameLocationGemParamters,
        ]));

        $response->assertOk();
        $response->assertSee('+0.05-0.10%');
        $response->assertSee('text-green-700 dark:text-green-400', false);
        $response->assertSee('text-red-700 dark:text-red-400', false);
        $response->assertSeeInOrder(['Description', 'Location formatting description.', 'Overview']);
        $response->assertSee('What this setup controls');
        $response->assertSee('Player reward modifiers');
        $response->assertSee('passive training, and crafting skill gains.');
        $response->assertDontSee('any map-only character power reduction.');
        $response->assertSee('border-blue-300 bg-blue-50', false);
        $response->assertDontSee('Character Power Reduction Range');
        $this->assertSame(1, substr_count($response->getContent(), '>Description</h3>'));
    }

    public function testMapGemEmptyStatFieldsDoNotRenderAsNotAvailable(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters([
            'description' => 'Present description.',
            'character_xp_bonus_range' => null,
            'gold_gain_range' => null,
            'crafting_skill_bonus_range' => null,
            'unique_item_drop_chance_increase_range' => null,
            'mythic_item_drop_chance_increase_range' => null,
            'cosmic_item_drop_chance_increase_range' => null,
            'ascended_item_drop_chance_increase_range' => null,
            'character_power_reduction_range' => null,
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertDontSee('N/A');
    }

    public function testLocationGemEmptyStatFieldsDoNotRenderAsNotAvailable(): void
    {
        $gameLocationGemParamters = $this->createGameLocationGemParamters([
            'description' => 'Present location description.',
            'character_xp_bonus_range' => null,
            'gold_gain_range' => null,
            'crafting_skill_bonus_range' => null,
            'unique_item_drop_chance_increase_range' => null,
            'mythic_item_drop_chance_increase_range' => null,
            'cosmic_item_drop_chance_increase_range' => null,
            'ascended_item_drop_chance_increase_range' => null,
        ]);

        $response = $this->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamters' => $gameLocationGemParamters,
        ]));

        $response->assertOk();
        $response->assertDontSee('N/A');
    }

    public function testMapGemOverviewDoesNotRenderEmptyMonsterAtonement(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters([
            'monster_atonement' => null,
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertDontSee('>Monster Atonement</dt>', false);
    }

    public function testMapGemOverviewDoesNotRenderEmptyMonsterAtonementRange(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters([
            'monster_atonement_range' => null,
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertDontSee('>Monster Atonement Range</dt>', false);
    }

    public function testMapGemOverviewDoesNotRenderEmptyCraftingSkills(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters([
            'crafting_skill_ids' => [],
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertDontSee('>Crafting Skills</dt>', false);
    }

    public function testMapGemSectionWithNoVisibleFieldsDoesNotRenderItsCard(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters();

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertDontSee('Enemy Combat');
    }

    public function testMapGemSectionWithNoVisibleFieldsDoesNotRenderItsBlueInfoAlert(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters();

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertDontSee('Enemy combat modifiers');
    }

    public function testMapGemShowLayoutIncludesItemsStartClass(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters();

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertSee('lg:items-start', false);
    }

    public function testMapGemShowBlueInfoAlertsIncludeSelfStartClass(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters();

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertSee('self-start', false);
    }

    public function testMapGemDlUsesFourColumnGridWithNoInnerWrappers(): void
    {
        $gameMapGemParamters = $this->createGameMapGemParamters([
            'character_xp_bonus_range' => '0.05-0.10',
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamters' => $gameMapGemParamters,
        ]));

        $response->assertOk();
        $response->assertSee('lg:grid-cols-[minmax(10rem,16rem)_minmax(0,1fr)_minmax(10rem,16rem)_minmax(0,1fr)]', false);
        $response->assertDontSee('sm:grid-cols-[minmax(12rem,18rem)_minmax(0,1fr)]', false);
    }

    public function testLocationGemDlUsesFourColumnGridWithNoInnerWrappers(): void
    {
        $gameLocationGemParamters = $this->createGameLocationGemParamters([
            'character_xp_bonus_range' => '0.05-0.10',
        ]);

        $response = $this->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamters' => $gameLocationGemParamters,
        ]));

        $response->assertOk();
        $response->assertSee('lg:grid-cols-[minmax(10rem,16rem)_minmax(0,1fr)_minmax(10rem,16rem)_minmax(0,1fr)]', false);
        $response->assertDontSee('sm:grid-cols-[minmax(12rem,18rem)_minmax(0,1fr)]', false);
    }
}
