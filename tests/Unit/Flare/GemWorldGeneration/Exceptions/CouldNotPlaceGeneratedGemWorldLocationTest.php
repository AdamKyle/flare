<?php

namespace Tests\Unit\Flare\GemWorldGeneration\Exceptions;

use App\Flare\GemWorldGeneration\Exceptions\CouldNotPlaceGeneratedGemWorldLocation;
use Tests\TestCase;

class CouldNotPlaceGeneratedGemWorldLocationTest extends TestCase
{
    public function test_with_context_builds_a_descriptive_message(): void
    {
        $exception = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: 'regular',
            attempts: 5,
            mapId: 1,
            mapPath: 'generated-gem-worlds/fiery.png',
            parentMapName: 'Surface',
            profileName: 'Fiery',
            imageWidth: 100,
            imageHeight: 100,
            imageLoaded: true,
            diagnostics: [
                'Simple diagnostic' => 'value',
                'Array diagnostic' => ['red' => 1, 'green' => 2],
            ],
        );

        $this->assertStringContainsString('Location type: regular', $exception->getMessage());
        $this->assertStringContainsString('Attempts: 5', $exception->getMessage());
        $this->assertStringContainsString('Image status: loaded', $exception->getMessage());
        $this->assertStringContainsString('Simple diagnostic: value', $exception->getMessage());
        $this->assertStringContainsString('Array diagnostic: {"red":1,"green":2}', $exception->getMessage());
    }

    public function test_with_context_reports_failed_to_load_when_the_image_did_not_load(): void
    {
        $exception = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: 'port',
            attempts: 1,
            mapId: 2,
            mapPath: 'generated-gem-worlds/fiery.png',
            parentMapName: 'Surface',
            profileName: 'Fiery',
            imageWidth: 0,
            imageHeight: 0,
            imageLoaded: false,
        );

        $this->assertStringContainsString('Image status: failed to load', $exception->getMessage());
    }
}
