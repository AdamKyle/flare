<?php

namespace Tests\Unit\Admin\Jobs;

use App\Flare\Mail\GenericMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Admin\Jobs\LevelTestCharacter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;

class LevelTestCharacterTest extends TestCase
{
    use RefreshDatabase;
}
