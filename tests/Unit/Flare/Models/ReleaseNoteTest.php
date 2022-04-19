<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\ReleaseNote;
use Tests\TestCase;
use Tests\Traits\CreateReleaseNotes;

class ReleaseNoteTest extends TestCase
{
    use RefreshDatabase, CreateReleaseNotes;

    public function testMakeSureReleaseNoteExists() {

        $this->createReleaseNotes([
            'name'         => 'sample',
            'url'          => 'http://google.ca',
            'release_date' => now(),
            'body'         => 'Sample',
            'version'      => '2.0.0',
        ]);

        $this->assertNotNull(ReleaseNote::first());
    }
}
