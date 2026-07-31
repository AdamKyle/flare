<?php

namespace Tests\Unit\Game\Character\Concerns;

use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Character\Exceptions\MissingInventoryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class FetchEquippedTest extends TestCase
{
    use RefreshDatabase;

    public function testMissingInventoryFlagsUserAndImmediatelyThrowsExplicitException(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->inventory()->delete();
        $fetcher = new class {
            use FetchEquipped;
        };

        try {
            $fetcher->fetchEquipped($character->refresh());
            $this->fail('The missing-inventory exception was not thrown.');
        } catch (MissingInventoryException $exception) {
            $this->assertSame('The character inventory is missing.', $exception->getMessage());
        }

        $this->assertTrue($character->user->refresh()->will_be_deleted);
    }
}
