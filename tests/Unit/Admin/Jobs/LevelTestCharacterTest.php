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

    public function testLevelTestCharacter()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->giveSnapShot()->getCharacter();

        LevelTestCharacter::dispatch($character, 1);

        $this->assertTrue($character->snapshots->isNotEmpty());
        $this->assertNotempty($character->snapShots->first()->snap_shot);
    }

    public function testNotUpdatingRace() {
        Mail::fake();

        Cache::put('updating-test-characters', true);

        $character = (new CharacterFactory)->createBaseCharacter()->giveSnapShot()->getCharacter();

        LevelTestCharacter::dispatch($character, 1, $character->user);

        $this->assertTrue($character->snapshots->isNotEmpty());
        $this->assertNotempty($character->snapShots->first()->snap_shot);

        Mail::assertSent(GenericMail::class);

        $this->assertFalse(Cache::has('updating-test-character'));
    }

    public function testUpdatingRace() {
        Mail::fake();

        Cache::put('updating-test-characters', true);

        $character = (new CharacterFactory)->createBaseCharacter()->giveSnapShot()->getCharacter();

        LevelTestCharacter::dispatch($character, 1, $character->user, true);

        $this->assertTrue($character->snapshots->isNotEmpty());
        $this->assertNotempty($character->snapShots->first()->snap_shot);

        Mail::assertSent(GenericMail::class);

        $this->assertFalse(Cache::has('updating-test-character'));
    }


}
