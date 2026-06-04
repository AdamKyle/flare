<?php

namespace Tests\Unit\Game\Battle\Listeners;

use App\Flare\Models\Character;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Jobs\AttackTimeOutJob;
use App\Game\Battle\Listeners\AttackTimeOutListener;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Core\Events\ShowTimeOutEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class AttackTimeOutListenerTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        Carbon::setTestNow();

        $this->character = null;

        parent::tearDown();
    }

    public function test_forty_nine_point_nine_five_percent_fight_timeout_modifier_waits_about_seven_point_five_seconds(): void
    {
        Event::fake();
        Queue::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $characterStatBuilder = Mockery::mock(CharacterStatBuilder::class);
        $characterStatBuilder->shouldReceive('setCharacter')
            ->once()
            ->with($this->character)
            ->andReturnSelf();
        $characterStatBuilder->shouldReceive('buildTimeOutModifier')
            ->once()
            ->with('fight_time_out')
            ->andReturn(0.4995);

        $listener = new AttackTimeOutListener($characterStatBuilder);

        $listener->handle(new AttackTimeOutEvent($this->character));

        $this->assertFalse($this->character->refresh()->can_attack);
        Queue::assertPushed(AttackTimeOutJob::class, function (AttackTimeOutJob $job) use ($now): bool {
            return abs($job->delay->diffInMilliseconds($now, true) - 7500) <= 5;
        });
        Event::assertDispatched(ShowTimeOutEvent::class, function (ShowTimeOutEvent $event): bool {
            return abs($event->forLength - 7.5025) < 0.0001;
        });
    }

    public function test_one_hundred_percent_fight_timeout_modifier_waits_five_seconds(): void
    {
        Event::fake();
        Queue::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $characterStatBuilder = Mockery::mock(CharacterStatBuilder::class);
        $characterStatBuilder->shouldReceive('setCharacter')
            ->once()
            ->with($this->character)
            ->andReturnSelf();
        $characterStatBuilder->shouldReceive('buildTimeOutModifier')
            ->once()
            ->with('fight_time_out')
            ->andReturn(1.0);

        $listener = new AttackTimeOutListener($characterStatBuilder);

        $listener->handle(new AttackTimeOutEvent($this->character));

        $this->assertFalse($this->character->refresh()->can_attack);
        Queue::assertPushed(AttackTimeOutJob::class, function (AttackTimeOutJob $job) use ($now): bool {
            return $job->delay->equalTo($now->copy()->addSeconds(5));
        });
    }

    public function test_dead_character_waits_twenty_seconds(): void
    {
        Event::fake();
        Queue::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $this->character->update([
            'is_dead' => true,
        ]);

        $characterStatBuilder = Mockery::mock(CharacterStatBuilder::class);
        $characterStatBuilder->shouldNotReceive('setCharacter');
        $characterStatBuilder->shouldNotReceive('buildTimeOutModifier');

        $listener = new AttackTimeOutListener($characterStatBuilder);

        $listener->handle(new AttackTimeOutEvent($this->character->refresh()));

        $this->assertFalse($this->character->refresh()->can_attack);
        Queue::assertPushed(AttackTimeOutJob::class, function (AttackTimeOutJob $job) use ($now): bool {
            return $job->delay->equalTo($now->copy()->addSeconds(20));
        });
    }
}
