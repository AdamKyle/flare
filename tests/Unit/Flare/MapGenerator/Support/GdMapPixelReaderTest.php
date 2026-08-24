<?php

namespace Tests\Unit\Flare\MapGenerator\Support;

use App\Flare\MapGenerator\Support\GdMapPixelReader;
use RuntimeException;
use Tests\TestCase;

class GdMapPixelReaderTest extends TestCase
{
    public function test_reader_throws_when_the_image_data_cannot_be_decoded(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not load generated gem world map image.');

        new GdMapPixelReader('not a real image');
    }
}
