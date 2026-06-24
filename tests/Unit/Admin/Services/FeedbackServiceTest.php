<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\FeedbackService;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testOneBugAndNoSuggestionsGivesBugOneHundredPercentShare(): void
    {
        SuggestionAndBugs::factory()->create(['type' => FeedbackType::BUG]);

        $data = resolve(FeedbackService::class)->gatherFeedbackData();

        $this->assertSame(100.0, $data['bugsPercentage']);
        $this->assertSame(0.0, $data['suggestionPercentage']);
    }

    public function testNoBugsAndOneSuggestionGivesSuggestionOneHundredPercentShare(): void
    {
        SuggestionAndBugs::factory()->create(['type' => FeedbackType::SUGGESTION]);

        $data = resolve(FeedbackService::class)->gatherFeedbackData();

        $this->assertSame(0.0, $data['bugsPercentage']);
        $this->assertSame(100.0, $data['suggestionPercentage']);
    }

    public function testOneBugAndOneSuggestionEachGiveFiftyPercentShare(): void
    {
        SuggestionAndBugs::factory()->create(['type' => FeedbackType::BUG]);
        SuggestionAndBugs::factory()->create(['type' => FeedbackType::SUGGESTION]);

        $data = resolve(FeedbackService::class)->gatherFeedbackData();

        $this->assertSame(50.0, $data['bugsPercentage']);
        $this->assertSame(50.0, $data['suggestionPercentage']);
    }

    public function testNoFeedbackGivesBothZeroPercentShare(): void
    {
        $data = resolve(FeedbackService::class)->gatherFeedbackData();

        $this->assertSame(0.0, $data['bugsPercentage']);
        $this->assertSame(0.0, $data['suggestionPercentage']);
    }
}
