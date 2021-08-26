<?php

namespace Tests\Unit\Flare\Values;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Values\GameVersionHelper;
use Tests\TestCase;
use Tests\Traits\CreateReleaseNotes;

class GameVersionHelperTest extends TestCase {

    use RefreshDatabase, CreateReleaseNotes;

    public function testGetReleaseVersion() {
        $releaseNotes = $this->createReleaseNotes([
            'name' => 'Sample',
            'version' => '1.0.0',
            'url' => 'http://google.ca',
            'release_date' => now(),
            'body' => 'Sample',
        ]);

        $this->assertEquals($releaseNotes->version, GameVersionHelper::version());
    }

}
