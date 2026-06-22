<?php

namespace Tests\Feature\Http\Controllers;

use App\Flare\View\Livewire\Info\LocationGems;
use App\Flare\View\Livewire\Info\MapGems;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMapGemParamter;

class InfoGemParamtersControllerTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMapGemParamter, RefreshDatabase;

    public function testGuestCanViewMapGemsList(): void
    {
        $response = $this->call('GET', route('info.page.map-gems.list'));

        $response->assertOk();
        $response->assertSee('Map Gems');
    }

    public function testGuestCanViewMapGemShow(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter([
            'crafting_skill_bonus_range' => '0.25-0.75',
            'description' => 'Public map gem description.',
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertSee($gameMapGemParamter->name);
        $response->assertSee($gameMapGemParamter->gameMap->name);
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
        $gameLocationGemParamter = $this->createGameLocationGemParamter([
            'crafting_skill_bonus_range' => '0.25-0.75',
            'description' => 'Public location gem description.',
        ]);

        $response = $this->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamter' => $gameLocationGemParamter,
        ]));

        $response->assertOk();
        $response->assertSee($gameLocationGemParamter->name);
        $response->assertSee($gameLocationGemParamter->location->name);
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
        $gameMapGemParamter = $this->createGameMapGemParamter();

        $response = $this->actingAs($user)->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertDontSee(route('admin.map-gems.edit', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]), false);
    }

    public function testNonAdminCanViewLocationGemInformationWithoutEditLink(): void
    {
        $user = (new CharacterFactory)
            ->createBaseCharacter(assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter()
            ->user;
        $gameLocationGemParamter = $this->createGameLocationGemParamter();

        $response = $this->actingAs($user)->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamter' => $gameLocationGemParamter,
        ]));

        $response->assertOk();
        $response->assertDontSee(route('admin.location-gems.edit', [
            'gameLocationGemParamter' => $gameLocationGemParamter,
        ]), false);
    }

    public function testMapGemShowDisplaysNotAvailableForEmptyDescription(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter([
            'description' => null,
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['Description', 'N/A']);
    }

    public function testLocationGemShowDisplaysNotAvailableForEmptyDescription(): void
    {
        $gameLocationGemParamter = $this->createGameLocationGemParamter([
            'description' => null,
        ]);

        $response = $this->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamter' => $gameLocationGemParamter,
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['Description', 'N/A']);
    }

    public function testMapGemsLivewireTableRendersWithRecord(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter();

        Livewire::test(MapGems::class)
            ->assertSee($gameMapGemParamter->name)
            ->assertSee(route('info.page.map-gems.show', [
                'gameMapGemParamter' => $gameMapGemParamter,
            ]), false);
    }

    public function testLocationGemsLivewireTableRendersWithRecord(): void
    {
        $gameLocationGemParamter = $this->createGameLocationGemParamter();

        Livewire::test(LocationGems::class)
            ->assertSee($gameLocationGemParamter->name)
            ->assertSee(route('info.page.location-gems.show', [
                'gameLocationGemParamter' => $gameLocationGemParamter,
            ]), false);
    }

    public function testMapGemShowFormatsBeneficialAndHarmfulRanges(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter([
            'description' => 'Formatting test description.',
            'character_xp_bonus_range' => '0.05-0.10',
            'enemy_strength_increase_range' => '0.05-0.10',
            'character_power_reduction_range' => '0.05-0.10',
            'monster_gold_drop_increase_range' => '0.05-0.10',
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
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
        $gameLocationGemParamter = $this->createGameLocationGemParamter([
            'description' => 'Location formatting description.',
            'unique_item_drop_chance_increase_range' => '0.05-0.10',
            'enemy_counter_chance_range' => '0.05-0.10',
            'enemy_quest_item_drop_chance_increase_range' => '0.05-0.10',
        ]);

        $response = $this->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamter' => $gameLocationGemParamter,
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
        $gameMapGemParamter = $this->createGameMapGemParamter([
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
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertDontSee('N/A');
    }

    public function testLocationGemEmptyStatFieldsDoNotRenderAsNotAvailable(): void
    {
        $gameLocationGemParamter = $this->createGameLocationGemParamter([
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
            'gameLocationGemParamter' => $gameLocationGemParamter,
        ]));

        $response->assertOk();
        $response->assertDontSee('N/A');
    }

    public function testMapGemOverviewDoesNotRenderEmptyMonsterAtonement(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter([
            'monster_atonement' => null,
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertDontSee('>Monster Atonement</dt>', false);
    }

    public function testMapGemOverviewDoesNotRenderEmptyMonsterAtonementRange(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter([
            'monster_atonement_range' => null,
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertDontSee('>Monster Atonement Range</dt>', false);
    }

    public function testMapGemOverviewDoesNotRenderEmptyCraftingSkills(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter([
            'crafting_skill_ids' => [],
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertDontSee('>Crafting Skills</dt>', false);
    }

    public function testMapGemSectionWithNoVisibleFieldsDoesNotRenderItsCard(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter();

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertDontSee('Enemy Combat');
    }

    public function testMapGemSectionWithNoVisibleFieldsDoesNotRenderItsBlueInfoAlert(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter();

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertDontSee('Enemy combat modifiers');
    }

    public function testMapGemShowLayoutIncludesItemsStartClass(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter();

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertSee('lg:items-start', false);
    }

    public function testMapGemShowBlueInfoAlertsIncludeSelfStartClass(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter();

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertSee('self-start', false);
    }

    public function testMapGemDlUsesFourColumnGridWithNoInnerWrappers(): void
    {
        $gameMapGemParamter = $this->createGameMapGemParamter([
            'character_xp_bonus_range' => '0.05-0.10',
        ]);

        $response = $this->call('GET', route('info.page.map-gems.show', [
            'gameMapGemParamter' => $gameMapGemParamter,
        ]));

        $response->assertOk();
        $response->assertSee('lg:grid-cols-[minmax(10rem,16rem)_minmax(0,1fr)_minmax(10rem,16rem)_minmax(0,1fr)]', false);
        $response->assertDontSee('sm:grid-cols-[minmax(12rem,18rem)_minmax(0,1fr)]', false);
    }

    public function testLocationGemDlUsesFourColumnGridWithNoInnerWrappers(): void
    {
        $gameLocationGemParamter = $this->createGameLocationGemParamter([
            'character_xp_bonus_range' => '0.05-0.10',
        ]);

        $response = $this->call('GET', route('info.page.location-gems.show', [
            'gameLocationGemParamter' => $gameLocationGemParamter,
        ]));

        $response->assertOk();
        $response->assertSee('lg:grid-cols-[minmax(10rem,16rem)_minmax(0,1fr)_minmax(10rem,16rem)_minmax(0,1fr)]', false);
        $response->assertDontSee('sm:grid-cols-[minmax(12rem,18rem)_minmax(0,1fr)]', false);
    }
}
