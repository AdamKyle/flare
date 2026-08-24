<?php

namespace Tests\Setup\GemWorldGeneration;

use App\Flare\GemWorldGeneration\Services\GemWorldImageGenerator;
use App\Flare\GemWorldGeneration\Services\GemWorldPlaneGenerationSettings;
use App\Flare\GemWorldGeneration\Values\GemWorldGenerationConfig;
use App\Flare\MapGenerator\Contracts\LandMapImageFactory;
use App\Flare\MapGenerator\Support\GdPngImageWriter;
use Mockery;

class GemWorldImageGeneratorFactory
{
    public function build(?LandMapImageFactory $landMapImageFactory = null, ?GdPngImageWriter $imageWriter = null): GemWorldImageGenerator
    {
        if (is_null($landMapImageFactory)) {
            $landMapImageFactory = Mockery::mock(LandMapImageFactory::class);
            $landMapImageFactory->shouldReceive('build')->andReturn('fake-image-resource');
        }

        if (is_null($imageWriter)) {
            $imageWriter = Mockery::mock(GdPngImageWriter::class);
            $imageWriter->shouldReceive('encodeAndStore');
        }

        return new GemWorldImageGenerator(
            new GemWorldPlaneGenerationSettings(),
            $landMapImageFactory,
            $imageWriter,
            GemWorldGenerationConfig::fromConfig(),
        );
    }
}
