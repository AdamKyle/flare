<?php

namespace Tests\Unit\Game\Automation\Concerns;

use App\Flare\Models\Character;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ChecksAutomationRestrictionsTest extends TestCase
{
    use MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_automation_restriction_json_response_returns_null_when_not_restricted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->instance(AutomationRestrictionService::class, Mockery::mock(AutomationRestrictionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('blockedContext')->andReturnNull();
        }));

        $subject = new class
        {
            use ChecksAutomationRestrictions;

            public function check(Character $character): ?JsonResponse
            {
                return $this->automationRestrictionJsonResponse($character, 'move');
            }
        };

        $this->assertNull($subject->check($character));
    }

    public function test_automation_restriction_json_response_returns_422_when_restricted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->instance(AutomationRestrictionService::class, Mockery::mock(AutomationRestrictionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('blockedContext')->andReturn(['message' => 'You cannot do that right now.']);
        }));

        $subject = new class
        {
            use ChecksAutomationRestrictions;

            public function check(Character $character): ?JsonResponse
            {
                return $this->automationRestrictionJsonResponse($character, 'move');
            }
        };

        $response = $subject->check($character);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('You cannot do that right now.', $response->getData(true)['message']);
    }

    public function test_automation_restriction_error_result_returns_null_when_not_restricted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->instance(AutomationRestrictionService::class, Mockery::mock(AutomationRestrictionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('blockedContext')->andReturnNull();
        }));

        $subject = new class
        {
            use ChecksAutomationRestrictions;

            public function check(Character $character): ?array
            {
                return $this->automationRestrictionErrorResult($character, 'move');
            }
        };

        $this->assertNull($subject->check($character));
    }

    public function test_automation_restriction_error_result_returns_message_and_status_when_restricted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->instance(AutomationRestrictionService::class, Mockery::mock(AutomationRestrictionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('blockedContext')->andReturn(['message' => 'You cannot do that right now.']);
        }));

        $subject = new class
        {
            use ChecksAutomationRestrictions;

            public function check(Character $character): ?array
            {
                return $this->automationRestrictionErrorResult($character, 'move');
            }
        };

        $result = $subject->check($character);

        $this->assertSame('You cannot do that right now.', $result['message']);
        $this->assertSame(422, $result['status']);
    }

    public function test_send_automation_restriction_message_returns_false_when_not_restricted(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->instance(AutomationRestrictionService::class, Mockery::mock(AutomationRestrictionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('blockedContext')->andReturnNull();
        }));

        $subject = new class
        {
            use ChecksAutomationRestrictions;

            public function check(Character $character): bool
            {
                return $this->sendAutomationRestrictionMessage($character, 'move');
            }
        };

        $this->assertFalse($subject->check($character));
        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_send_automation_restriction_message_dispatches_event_and_returns_true_when_restricted(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->instance(AutomationRestrictionService::class, Mockery::mock(AutomationRestrictionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('blockedContext')->andReturn(['message' => 'You cannot do that right now.']);
        }));

        $subject = new class
        {
            use ChecksAutomationRestrictions;

            public function check(Character $character): bool
            {
                return $this->sendAutomationRestrictionMessage($character, 'move');
            }
        };

        $this->assertTrue($subject->check($character));
        Event::assertDispatched(ServerMessageEvent::class);
    }
}
