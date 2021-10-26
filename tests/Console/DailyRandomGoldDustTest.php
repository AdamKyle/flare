<?php

namespace Tests\Console;


use App\Flare\Jobs\DailyGoldDustJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;

class DailyRandomGoldDustTest extends TestCase
{
    use RefreshDatabase;

    public function testDailyGoldDust()
    {

        Queue::fake();

        (new CharacterFactory())->createBaseCharacter()->getCharacter(false);

        $this->assertEquals(0, $this->artisan('daily:gold-dust'));

        Queue::assertPushed(DailyGoldDustJob::class);
    }
}
