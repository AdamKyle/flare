<?php

use Tests\Traits\CreateReleaseNotes;

uses(CreateReleaseNotes::class);

test('release notes list page shows release notes', function () {
    $releaseNotes = $this->createReleaseNotes([
        'name' => 'Sample',
        'version' => '1.0.0',
        'url' => 'http://google.ca',
        'release_date' => now(),
        'body' => 'Sample',
    ]);

    $response = $this->get(route('releases.list'));

    $response->assertSee($releaseNotes->name);
});
