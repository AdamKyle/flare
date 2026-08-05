<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class NonAdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_layout_source_contains_no_livewire_or_alpine(): void
    {
        $layoutSource = File::get(resource_path('views/layouts/app.blade.php'));

        $this->assertStringNotContainsString('@livewireStyles', $layoutSource);
        $this->assertStringNotContainsString('@livewireScriptConfig', $layoutSource);
        $this->assertStringNotContainsString('@livewire(', $layoutSource);
        $this->assertStringNotContainsString('x-data', $layoutSource);
        $this->assertStringNotContainsString('x-show', $layoutSource);
        $this->assertStringNotContainsString('x-init', $layoutSource);
        $this->assertStringNotContainsString('x-cloak', $layoutSource);
        $this->assertStringNotContainsString('resources/js/vendor/alpine.js', $layoutSource);
        $this->assertStringNotContainsString('resources/js/vendor/livewire.js', $layoutSource);
        $this->assertStringNotContainsString('resources/js/vendor/livewire-data-tables.js', $layoutSource);
        $this->assertStringContainsString("@vite('resources/js/layouts/app-layout.ts')", $layoutSource);
    }

    public function test_information_layout_source_contains_no_livewire_or_alpine(): void
    {
        $layoutSource = File::get(resource_path('views/layouts/information.blade.php'));

        $this->assertStringNotContainsString('@livewireStyles', $layoutSource);
        $this->assertStringNotContainsString('@livewireScriptConfig', $layoutSource);
        $this->assertStringNotContainsString('@livewire(', $layoutSource);
        $this->assertStringNotContainsString('x-data', $layoutSource);
        $this->assertStringNotContainsString('resources/js/vendor/alpine.js', $layoutSource);
        $this->assertStringNotContainsString('resources/js/vendor/livewire.js', $layoutSource);
        $this->assertStringNotContainsString('resources/js/vendor/livewire-data-tables.js', $layoutSource);
    }

    public function test_alpine_vendor_file_no_longer_exists(): void
    {
        $this->assertFalse(File::exists(resource_path('js/vendor/alpine.js')));
    }

    public function test_authenticated_game_page_renders_without_livewire(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->actingAs($character->user)->call('GET', '/game/tops');
        $content = $response->getContent();

        $response->assertOk();
        $this->assertStringNotContainsString('livewireScriptConfig', $content);
        $this->assertStringNotContainsString('Livewire Styles', $content);
        $this->assertStringNotContainsString('wire:', $content);
        $this->assertStringNotContainsString('x-data', $content);
    }

    public function test_public_information_page_renders_without_livewire(): void
    {
        $response = $this->call('GET', '/information/map-gems');
        $content = $response->getContent();

        $response->assertOk();
        $this->assertStringNotContainsString('livewireScriptConfig', $content);
        $this->assertStringNotContainsString('Livewire Styles', $content);
        $this->assertStringNotContainsString('wire:', $content);
        $this->assertStringNotContainsString('x-data', $content);
    }
}
