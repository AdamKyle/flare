<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Services\FactionLoyaltyRewardRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class FactionLoyaltyRewardRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?FactionLoyaltyRewardRequestService $factionLoyaltyRewardRequestService;

    public function setUp(): void
    {
        parent::setUp();

        $this->factionLoyaltyRewardRequestService = resolve(FactionLoyaltyRewardRequestService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->factionLoyaltyRewardRequestService = null;
    }

    public function testEnqueueCreatesNewRequestWhenNoExistingRequestExists(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $request = $this->factionLoyaltyRewardRequestService->enqueue(
            $character->id,
            99,
            1,
            ['xp_amount' => 1000, 'gold_amount' => 1_000_000],
        );

        $this->assertEquals($character->id, $request->character_id);
        $this->assertEquals(BattleRewardRequestSourceType::FACTION_LOYALTY, $request->source_type);
        $this->assertEquals("faction_loyalty:{$character->id}:99:1", $request->source_id);
        $this->assertEquals(1000, $request->handler_payload['xp_amount']);
    }

    public function testEnqueueReturnsPendingRequestWithoutCreatingANewOne(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $sourceId = "faction_loyalty:{$character->id}:99:1";

        CharacterBattleRewardRequest::create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::SECOND,
            'source_type' => BattleRewardRequestSourceType::FACTION_LOYALTY,
            'source_id' => $sourceId,
            'handler_payload' => ['xp_amount' => 1000],
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        $this->factionLoyaltyRewardRequestService->enqueue(
            $character->id,
            99,
            1,
            ['xp_amount' => 2000],
        );

        $this->assertCount(1, CharacterBattleRewardRequest::where('character_id', $character->id)
            ->where('source_type', BattleRewardRequestSourceType::FACTION_LOYALTY)
            ->get()
        );
    }

    public function testEnqueueReturnsCompletedRequestWithoutCreatingANewOne(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $sourceId = "faction_loyalty:{$character->id}:99:1";

        CharacterBattleRewardRequest::create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::SECOND,
            'source_type' => BattleRewardRequestSourceType::FACTION_LOYALTY,
            'source_id' => $sourceId,
            'handler_payload' => ['xp_amount' => 1000],
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);

        $returned = $this->factionLoyaltyRewardRequestService->enqueue(
            $character->id,
            99,
            1,
            ['xp_amount' => 2000],
        );

        $this->assertEquals(BattleRewardRequestStatus::COMPLETED, $returned->status);
        $this->assertCount(1, CharacterBattleRewardRequest::where('character_id', $character->id)
            ->where('source_type', BattleRewardRequestSourceType::FACTION_LOYALTY)
            ->get()
        );
    }

    public function testDifferentRewardLevelsProduceDifferentRequests(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->factionLoyaltyRewardRequestService->enqueue(
            $character->id,
            99,
            1,
            ['xp_amount' => 1000],
        );

        $this->factionLoyaltyRewardRequestService->enqueue(
            $character->id,
            99,
            2,
            ['xp_amount' => 2000],
        );

        $this->assertCount(2, CharacterBattleRewardRequest::where('character_id', $character->id)
            ->where('source_type', BattleRewardRequestSourceType::FACTION_LOYALTY)
            ->get()
        );
    }
}
