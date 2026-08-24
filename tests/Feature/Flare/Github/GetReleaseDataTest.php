<?php

namespace Tests\Feature\Flare\Github;

use App\Flare\Github\Services\Github;
use App\Flare\Models\ReleaseNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateReleaseNotes;

class GetReleaseDataTest extends TestCase
{
    use CreateReleaseNotes, RefreshDatabase;

    public function test_no_release_notes_uses_the_authenticated_all_releases_path(): void
    {
        $github = Mockery::mock(Github::class);
        $github->shouldReceive('initiateClient')->once()->with(true)->andReturnSelf();
        $github->shouldReceive('fetchAllReleases')->once()->andReturn([
            [
                'name' => 'Version 1.0.0',
                'tag_name' => 'v1.0.0',
                'html_url' => 'https://github.com/AdamKyle/flare/releases/v1.0.0',
                'published_at' => '2026-01-01T00:00:00Z',
                'body' => 'Release body',
                'draft' => false,
            ],
        ]);

        $this->app->instance(Github::class, $github);

        $this->artisan('fetch:release-data')->assertExitCode(0);

        $this->assertSame(1, ReleaseNote::count());
    }

    public function test_draft_releases_are_skipped(): void
    {
        $github = Mockery::mock(Github::class);
        $github->shouldReceive('initiateClient')->once()->with(true)->andReturnSelf();
        $github->shouldReceive('fetchAllReleases')->once()->andReturn([
            [
                'name' => 'Version 1.0.0',
                'tag_name' => 'v1.0.0',
                'html_url' => 'https://github.com/AdamKyle/flare/releases/v1.0.0',
                'published_at' => '2026-01-01T00:00:00Z',
                'body' => 'Release body',
                'draft' => true,
            ],
        ]);

        $this->app->instance(Github::class, $github);

        $this->artisan('fetch:release-data')->assertExitCode(0);

        $this->assertSame(0, ReleaseNote::count());
    }

    public function test_multiple_non_draft_releases_are_all_stored(): void
    {
        $github = Mockery::mock(Github::class);
        $github->shouldReceive('initiateClient')->once()->with(true)->andReturnSelf();
        $github->shouldReceive('fetchAllReleases')->once()->andReturn([
            [
                'name' => 'Version 2.0.0',
                'tag_name' => 'v2.0.0',
                'html_url' => 'https://github.com/AdamKyle/flare/releases/v2.0.0',
                'published_at' => '2026-02-01T00:00:00Z',
                'body' => 'Release body 2',
                'draft' => false,
            ],
            [
                'name' => 'Version 1.0.0',
                'tag_name' => 'v1.0.0',
                'html_url' => 'https://github.com/AdamKyle/flare/releases/v1.0.0',
                'published_at' => '2026-01-01T00:00:00Z',
                'body' => 'Release body 1',
                'draft' => false,
            ],
        ]);

        $this->app->instance(Github::class, $github);

        $this->artisan('fetch:release-data')->assertExitCode(0);

        $this->assertSame(2, ReleaseNote::count());
    }

    public function test_existing_release_notes_uses_the_latest_release_path(): void
    {
        $this->createReleaseNotes(['name' => 'Version 0.1.0', 'version' => 'v0.1.0', 'url' => 'https://github.com/AdamKyle/flare/releases/v0.1.0', 'release_date' => now(), 'body' => 'Old body']);

        $github = Mockery::mock(Github::class);
        $github->shouldReceive('initiateClient')->once()->withNoArgs()->andReturnSelf();
        $github->shouldReceive('fetchLatestRelease')->once()->andReturn([
            'name' => 'Version 1.0.0',
            'tag_name' => 'v1.0.0',
            'html_url' => 'https://github.com/AdamKyle/flare/releases/v1.0.0',
            'published_at' => '2026-01-01T00:00:00Z',
            'body' => 'Release body',
            'draft' => false,
        ]);

        $this->app->instance(Github::class, $github);

        $this->artisan('fetch:release-data')->assertExitCode(0);

        $this->assertSame(2, ReleaseNote::count());
    }

    public function test_duplicate_url_is_not_created_twice(): void
    {
        $this->createReleaseNotes(['name' => 'Version 1.0.0', 'version' => 'v1.0.0', 'url' => 'https://github.com/AdamKyle/flare/releases/v1.0.0', 'release_date' => now(), 'body' => 'Old body']);

        $github = Mockery::mock(Github::class);
        $github->shouldReceive('initiateClient')->once()->withNoArgs()->andReturnSelf();
        $github->shouldReceive('fetchLatestRelease')->once()->andReturn([
            'name' => 'Version 1.0.0',
            'tag_name' => 'v1.0.0',
            'html_url' => 'https://github.com/AdamKyle/flare/releases/v1.0.0',
            'published_at' => '2026-01-01T00:00:00Z',
            'body' => 'Release body',
            'draft' => false,
        ]);

        $this->app->instance(Github::class, $github);

        $this->artisan('fetch:release-data')->assertExitCode(0);

        $this->assertSame(1, ReleaseNote::count());
    }

    public function test_persisted_release_note_fields_match_the_release_data(): void
    {
        $github = Mockery::mock(Github::class);
        $github->shouldReceive('initiateClient')->once()->with(true)->andReturnSelf();
        $github->shouldReceive('fetchAllReleases')->once()->andReturn([
            [
                'name' => 'Version 1.2.3',
                'tag_name' => 'v1.2.3',
                'html_url' => 'https://github.com/AdamKyle/flare/releases/v1.2.3',
                'published_at' => '2026-01-01T00:00:00Z',
                'body' => 'Release body',
                'draft' => false,
            ],
        ]);

        $this->app->instance(Github::class, $github);

        $this->artisan('fetch:release-data')->assertExitCode(0);

        $releaseNote = ReleaseNote::first();

        $this->assertSame('Version 1.2.3', $releaseNote->name);
        $this->assertSame('v1.2.3', $releaseNote->version);
        $this->assertSame('https://github.com/AdamKyle/flare/releases/v1.2.3', $releaseNote->url);
        $this->assertSame('2026-01-01 00:00:00', $releaseNote->release_date->toDateTimeString());
        $this->assertSame('Release body', $releaseNote->body);
    }
}
