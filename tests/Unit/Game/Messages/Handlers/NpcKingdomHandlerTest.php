<?php

namespace Tests\Unit\Game\Messages\Handlers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class NpcKingdomHandlerTest extends TestCase {
    use RefreshDatabase;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterF)

        Event::fake();
    }

    public function testCharacterCannotSettleKingdom() {

    }

    public function testCharacterCanHaveKingdom() {

    }

    public function testCharacterCanHaveKingdomBuildingsAreLocked() {

    }

    public function testCharacterCanHaveKingdomBuildingsAreUnlocked() {

    }

    public function testCharacterCannotAffordToHaveKingdom() {

    }
}
