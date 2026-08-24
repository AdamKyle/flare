<?php

namespace Tests\Unit\Flare\Support;

use App\Flare\Support\GameVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateReleaseNotes;

class GameVersionTest extends TestCase
{
    use CreateReleaseNotes, RefreshDatabase;

    public function test_returns_the_placeholder_version_when_no_release_notes_exist(): void
    {
        $this->assertSame('a.b.c', GameVersion::version());
    }

    public function test_returns_the_version_of_the_most_recent_release_note(): void
    {
        $this->createReleaseNotes([
            'name' => 'Older',
            'version' => 'v1.0.0',
            'url' => 'https://github.com/AdamKyle/flare/releases/v1.0.0',
            'release_date' => now()->subDay(),
            'body' => 'body',
        ]);
        $this->createReleaseNotes([
            'name' => 'Newer',
            'version' => 'v2.0.0',
            'url' => 'https://github.com/AdamKyle/flare/releases/v2.0.0',
            'release_date' => now(),
            'body' => 'body',
        ]);

        $this->assertSame('v2.0.0', GameVersion::version());
    }
}
