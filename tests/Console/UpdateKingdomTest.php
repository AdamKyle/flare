<?php

namespace Tests\Console;

use App\Flare\Jobs\UpdateKingdomJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class UpdateKingdomTest extends TestCase
{
    use RefreshDatabase;

    public function test_increase_kingdom_treasury()
    {
        Queue::fake();

        (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignUnits();

        $this->assertEquals(0, $this->artisan('update:kingdoms'));

        Queue::assertPushed(UpdateKingdomJob::class);

    }
}
