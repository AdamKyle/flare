<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveFilesTest extends TestCase
{
    use RefreshDatabase;

    public function testMoveFiles()
    {
        $this->assertEquals(0, $this->artisan('move:files'));
    }
}
