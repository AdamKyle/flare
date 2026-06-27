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

    public function test_one_bug_and_no_suggestions_gives_bug_one_hundred_percent_share(): void
    {
        SuggestionAndBugs::factory()->create(['type' => FeedbackType::BUG]);

        $data = resolve(FeedbackService::class)->gatherFeedbackData();

        $this->assertSame(100.0, $data['bugsPercentage']);
        $this->assertSame(0.0, $data['suggestionPercentage']);
    }

    public function test_no_bugs_and_one_suggestion_gives_suggestion_one_hundred_percent_share(): void
    {
        SuggestionAndBugs::factory()->create(['type' => FeedbackType::SUGGESTION]);

        $data = resolve(FeedbackService::class)->gatherFeedbackData();

        $this->assertSame(0.0, $data['bugsPercentage']);
        $this->assertSame(100.0, $data['suggestionPercentage']);
    }

    public function test_one_bug_and_one_suggestion_each_give_fifty_percent_share(): void
    {
        SuggestionAndBugs::factory()->create(['type' => FeedbackType::BUG]);
        SuggestionAndBugs::factory()->create(['type' => FeedbackType::SUGGESTION]);

        $data = resolve(FeedbackService::class)->gatherFeedbackData();

        $this->assertSame(50.0, $data['bugsPercentage']);
        $this->assertSame(50.0, $data['suggestionPercentage']);
    }

    public function test_no_feedback_gives_both_zero_percent_share(): void
    {
        $data = resolve(FeedbackService::class)->gatherFeedbackData();

        $this->assertSame(0.0, $data['bugsPercentage']);
        $this->assertSame(0.0, $data['suggestionPercentage']);
    }
}
