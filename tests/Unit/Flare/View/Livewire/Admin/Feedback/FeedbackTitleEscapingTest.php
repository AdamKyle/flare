<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Feedback;

use App\Flare\Models\SuggestionAndBugs;
use App\Flare\View\Livewire\Admin\Feedback\BugsList;
use App\Flare\View\Livewire\Admin\Feedback\SuggestionsList;
use App\Game\Core\Values\FeedbackType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTitleEscapingTest extends TestCase
{
    use RefreshDatabase;

    public function testBugTitleLinkEscapesStoredHtml(): void
    {
        $this->assertTitleIsEscaped(BugsList::class, FeedbackType::BUG);
    }

    public function testSuggestionTitleLinkEscapesStoredHtml(): void
    {
        $this->assertTitleIsEscaped(SuggestionsList::class, FeedbackType::SUGGESTION);
    }

    private function assertTitleIsEscaped(string $componentClass, string $type): void
    {
        $title = '<script>alert("stored")</script>';
        $feedback = SuggestionAndBugs::create([
            'character_id' => null,
            'title' => $title,
            'type' => $type,
            'platform' => 'desktop',
            'description' => 'Security test',
            'uploaded_image_paths' => [],
        ]);

        $component = new $componentClass;
        $format = $component->columns()[1]->getFormatCallback();
        $html = $format($title, $feedback);

        $this->assertStringContainsString(e($title), $html);
        $this->assertStringNotContainsString($title, $html);
    }
}
