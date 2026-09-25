<?php

namespace Tests\Feature\Game\Core\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_page_renders_game_launcher_when_onboarding_is_complete(): void
    {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();
        $user->update(['show_intro_page' => false]);

        $response = $this->actingAs($user)->call('GET', '/game');
        $content = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('id="game-launcher"', $content);
        $this->assertStringNotContainsString('id="onboarding-launcher"', $content);
        $this->assertStringNotContainsString('livewireScriptConfig', $content);
        $this->assertStringNotContainsString('Livewire Styles', $content);
        $this->assertStringNotContainsString('x-data', $content);
        $this->assertStringNotContainsString('Game Data Management', $content);
    }

    public function test_game_page_renders_onboarding_launcher_when_intro_is_required(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter();
        $user = $characterFactory->getUser();
        $user->update(['show_intro_page' => true]);

        $response = $this->actingAs($user)->call('GET', '/game');
        $content = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('id="onboarding-launcher"', $content);
        $this->assertStringContainsString('data-character-id="'.$characterFactory->getCharacter()->id.'"', $content);
        $this->assertStringNotContainsString('id="game-launcher"', $content);
        $this->assertStringNotContainsString('livewireScriptConfig', $content);
        $this->assertStringNotContainsString('Livewire Styles', $content);
        $this->assertStringNotContainsString('x-data', $content);
        $this->assertStringNotContainsString('Game Data Management', $content);
    }

    public function test_game_layout_source_leaves_application_entry_to_child_views_and_loads_no_livewire_assets(): void
    {
        $layoutSource = File::get(resource_path('views/layouts/game.blade.php'));

        $this->assertStringContainsString("@stack('game-app')", $layoutSource);
        $this->assertStringNotContainsString("@vite('resources/js/game.ts')", $layoutSource);
        $this->assertStringNotContainsString('@livewireStyles', $layoutSource);
        $this->assertStringNotContainsString('@livewireScriptConfig', $layoutSource);
        $this->assertStringNotContainsString('vendor/livewire.js', $layoutSource);
        $this->assertStringNotContainsString('vendor/livewire-data-tables.js', $layoutSource);
        $this->assertStringNotContainsString('x-data', $layoutSource);
        $this->assertStringNotContainsString('x-init', $layoutSource);
        $this->assertStringNotContainsString('core-side-bar', $layoutSource);
        $this->assertStringNotContainsString('core-header', $layoutSource);
    }

    public function test_game_view_source_loads_only_the_game_entry(): void
    {
        $gameViewSource = File::get(resource_path('views/game/game.blade.php'));

        $this->assertStringContainsString("@extends('layouts.game')", $gameViewSource);
        $this->assertStringContainsString('game-launcher', $gameViewSource);
        $this->assertStringContainsString("@vite('resources/js/game.ts')", $gameViewSource);
        $this->assertStringNotContainsString('data-show-intro-page', $gameViewSource);
        $this->assertStringNotContainsString('resources/js/onboarding.ts', $gameViewSource);
    }

    public function test_onboarding_view_source_loads_only_the_onboarding_entry(): void
    {
        $onboardingViewSource = File::get(resource_path('views/game/onboarding.blade.php'));

        $this->assertStringContainsString("@extends('layouts.game')", $onboardingViewSource);
        $this->assertStringContainsString('onboarding-launcher', $onboardingViewSource);
        $this->assertStringContainsString('resources/js/onboarding.ts', $onboardingViewSource);
        $this->assertStringNotContainsString('resources/js/game.ts', $onboardingViewSource);
    }
}
