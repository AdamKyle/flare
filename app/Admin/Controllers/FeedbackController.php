<?php

namespace App\Admin\Controllers;

use App\Flare\Github\Services\Markdown;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use App\Http\Controllers\Controller;

class FeedbackController extends Controller
{
    public function __construct(private readonly Markdown $markdown) {}

    public function bugs()
    {
        return view('admin.feedback.bugs-list');
    }

    public function bug(int $bug)
    {

        $foundBug = SuggestionAndBugs::where('type', FeedbackType::BUG)->where('id', $bug)->first();

        $cleanedUpDescription = $this->markdown->cleanMarkdown($foundBug->description);

        $renderedHtml = $this->markdown->convertToHtml($cleanedUpDescription);

        return view('admin.feedback.bug', compact('foundBug', 'renderedHtml'));
    }

    public function suggestions()
    {
        return view('admin.feedback.suggestions-list');
    }

    public function suggestion(int $suggestion)
    {
        $foundBug = SuggestionAndBugs::where('type', FeedbackType::SUGGESTION)->where('id', $suggestion)->first();

        $cleanedUpDescription = $this->markdown->cleanMarkdown($foundBug->description);

        $renderedHtml = $this->markdown->convertToHtml($cleanedUpDescription);

        return view('admin.feedback.suggestion', compact('foundBug', 'renderedHtml'));
    }

    public function deleteFeedback(int $feedbackId)
    {
        $foundFeedback = SuggestionAndBugs::find($feedbackId);

        if (is_null($foundFeedback)) {
            return redirect()->back()->with('error', 'Feedback not found.');
        }

        $foundFeedback->delete();

        return redirect()->back()->with('success', 'Feedback deleted.');
    }
}
