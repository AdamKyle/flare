<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Feedback;

use App\Flare\Models\SuggestionAndBugs;
use App\Flare\View\Livewire\Admin\Feedback\BugsList;
use App\Flare\View\Livewire\Admin\Feedback\SuggestionsList;
use App\Game\Core\Values\FeedbackType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FeedbackTitleEscapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_bug_title_link_escapes_stored_html(): void
    {
        $this->assertTitleIsEscaped(BugsList::class, FeedbackType::BUG);
    }

    public function test_suggestion_title_link_escapes_stored_html(): void
    {
        $this->assertTitleIsEscaped(SuggestionsList::class, FeedbackType::SUGGESTION);
    }

    public function test_bug_detail_title_escapes_stored_html(): void
    {
        $this->assertDetailTitleIsEscaped('admin.feedback.bug', FeedbackType::BUG);
    }

    public function test_suggestion_detail_title_escapes_stored_html(): void
    {
        $this->assertDetailTitleIsEscaped('admin.feedback.suggestion', FeedbackType::SUGGESTION);
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

    private function assertDetailTitleIsEscaped(string $view, string $type): void
    {
        view()->share('errors', new ViewErrorBag);

        $title = '<script>alert("stored")</script>';
        $feedback = SuggestionAndBugs::create([
            'character_id' => null,
            'title' => $title,
            'type' => $type,
            'platform' => 'desktop',
            'description' => 'Security test',
            'uploaded_image_paths' => [],
        ]);

        $html = view($view, [
            'foundBug' => $feedback,
            'renderedHtml' => '',
        ])->render();

        $this->assertStringContainsString(e($title), $html);
        $this->assertStringNotContainsString($title, $html);
    }
}
