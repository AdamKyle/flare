<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateReleaseNotes;

class ReleasePageControllerTest extends TestCase {

    use RefreshDatabase, CreateReleaseNotes;

    public function testViewReleaseNotes() {
        $releaseNotes = $this->createReleaseNotes([
            'name' => 'Sample',
            'version' => '1.0.0',
            'url' => 'http://google.ca',
            'release_date' => now(),
            'body' => 'Sample',
        ]);

        $this->visitRoute('releases.list')->see($releaseNotes->name);
    }
}
