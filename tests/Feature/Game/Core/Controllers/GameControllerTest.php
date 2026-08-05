<?php

namespace Tests\Feature\Game\Core\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_page_renders_launcher_without_livewire_or_alpine_or_admin_shell(): void
    {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $response = $this->actingAs($user)->call('GET', '/game');
        $content = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('id="game-launcher"', $content);
        $this->assertStringNotContainsString('livewireScriptConfig', $content);
        $this->assertStringNotContainsString('Livewire Styles', $content);
        $this->assertStringNotContainsString('x-data', $content);
        $this->assertStringNotContainsString('Game Data Management', $content);
    }

    public function test_game_layout_source_loads_game_entry_and_no_livewire_assets(): void
    {
        $layoutSource = File::get(resource_path('views/layouts/game.blade.php'));

        $this->assertStringContainsString("@vite('resources/js/game.ts')", $layoutSource);
        $this->assertStringNotContainsString('@livewireStyles', $layoutSource);
        $this->assertStringNotContainsString('@livewireScriptConfig', $layoutSource);
        $this->assertStringNotContainsString('vendor/livewire.js', $layoutSource);
        $this->assertStringNotContainsString('vendor/livewire-data-tables.js', $layoutSource);
        $this->assertStringNotContainsString('x-data', $layoutSource);
        $this->assertStringNotContainsString('x-init', $layoutSource);
        $this->assertStringNotContainsString('core-side-bar', $layoutSource);
        $this->assertStringNotContainsString('core-header', $layoutSource);
    }

    public function test_game_view_extends_game_layout(): void
    {
        $gameViewSource = File::get(resource_path('views/game/game.blade.php'));

        $this->assertStringContainsString("@extends('layouts.game')", $gameViewSource);
    }
}
