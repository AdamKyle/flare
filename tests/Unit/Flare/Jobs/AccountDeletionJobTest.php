<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Game\Character\Services\CharacterDeletion;
use App\Game\Messages\Events\GlobalMessageEvent;
use Exception;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateUserSiteAccessStatistics;

class AccountDeletionJobTest extends TestCase
{
    use CreateUserSiteAccessStatistics, RefreshDatabase;

    protected function tearDown(): void
    {
        UserSiteAccessStatistics::flushEventListeners();

        parent::tearDown();
    }

    public function test_dispatched_job_deletes_the_character_and_the_user(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $user = $character->user;

        $characterDeletion = Mockery::mock(CharacterDeletion::class);
        $characterDeletion->shouldReceive('deleteCharacterFromUser')
            ->once()
            ->with(Mockery::on(fn ($suppliedCharacter) => $suppliedCharacter->is($character)))
            ->andReturnUsing(fn ($suppliedCharacter) => $suppliedCharacter->delete());
        $this->app->instance(CharacterDeletion::class, $characterDeletion);

        AccountDeletionJob::dispatch($user);

        $this->assertNull(User::find($user->id));
    }

    public function test_dispatched_job_does_not_send_email_when_email_user_is_false(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $user = $character->user;

        $this->app->instance(CharacterDeletion::class, Mockery::mock(CharacterDeletion::class, function ($mock) use ($character) {
            $mock->shouldReceive('deleteCharacterFromUser')->once()->andReturnUsing(fn () => $character->delete());
        }));

        Mail::fake();

        AccountDeletionJob::dispatch($user, false);

        Mail::assertNothingSent();
    }

    public function test_dispatched_job_sends_confirmation_email_when_email_user_is_true(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $user = $character->user;

        $this->app->instance(CharacterDeletion::class, Mockery::mock(CharacterDeletion::class, function ($mock) use ($character) {
            $mock->shouldReceive('deleteCharacterFromUser')->once()->andReturnUsing(fn () => $character->delete());
        }));

        Mail::fake();

        AccountDeletionJob::dispatch($user, true);

        Mail::assertSent(GenericMail::class);
    }

    public function test_dispatched_job_logs_when_the_site_statistics_update_fails(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $user = $character->user;

        $this->app->instance(CharacterDeletion::class, Mockery::mock(CharacterDeletion::class, function ($mock) use ($character) {
            $mock->shouldReceive('deleteCharacterFromUser')->once()->andReturnUsing(fn () => $character->delete());
        }));

        $this->createUserSiteAccessStatistics(['amount_signed_in' => 3, 'amount_registered' => 1]);

        UserSiteAccessStatistics::creating(function () {
            throw new Exception('forced site statistics failure');
        });

        Log::shouldReceive('error')
            ->once()
            ->with('Account deletion site statistics update failed.', Mockery::on(fn ($context) => $context['user_id'] === $user->id && $context['exception'] instanceof Exception));
        Log::shouldReceive('error')->withAnyArgs()->andReturnNull()->byDefault();

        AccountDeletionJob::dispatch($user, false);

        $this->assertNull(User::find($user->id));
    }

    public function test_dispatched_job_logs_when_the_confirmation_email_fails(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $user = $character->user;
        $userEmail = $user->email;

        $this->app->instance(CharacterDeletion::class, Mockery::mock(CharacterDeletion::class, function ($mock) use ($character) {
            $mock->shouldReceive('deleteCharacterFromUser')->once()->andReturnUsing(fn () => $character->delete());
        }));

        Mail::shouldReceive('to')
            ->once()
            ->with($userEmail)
            ->andThrow(new Exception('forced mail failure'));

        Log::shouldReceive('error')
            ->once()
            ->with('Account deletion confirmation email failed.', Mockery::on(fn ($context) => $context['user_id'] === $user->id && $context['exception'] instanceof Exception));
        Log::shouldReceive('error')->withAnyArgs()->andReturnNull()->byDefault();

        AccountDeletionJob::dispatch($user, true);

        $this->assertNull(User::find($user->id));
    }

    public function test_dispatched_job_logs_when_the_global_message_event_fails(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $user = $character->user;
        $characterName = $character->name;

        $this->app->instance(CharacterDeletion::class, Mockery::mock(CharacterDeletion::class, function ($mock) use ($character) {
            $mock->shouldReceive('deleteCharacterFromUser')->once()->andReturnUsing(fn () => $character->delete());
        }));

        Mail::fake();

        $realDispatcher = $this->app['events'];
        $this->app->instance('events', new class($this->app, $realDispatcher) extends Dispatcher
        {
            public function __construct(protected $app, protected Dispatcher $realDispatcher)
            {
                parent::__construct($app);
            }

            public function dispatch($event, $payload = [], $halt = false)
            {
                if ($event instanceof GlobalMessageEvent) {
                    throw new Exception('forced global message failure');
                }

                return $this->realDispatcher->dispatch($event, $payload, $halt);
            }
        });

        Log::shouldReceive('error')
            ->once()
            ->with('Account deletion global message failed.', Mockery::on(fn ($context) => $context['user_id'] === $user->id && $context['character_name'] === $characterName && $context['exception'] instanceof Exception));
        Log::shouldReceive('error')->withAnyArgs()->andReturnNull()->byDefault();

        AccountDeletionJob::dispatch($user, true);

        $this->assertNull(User::find($user->id));
    }
}
