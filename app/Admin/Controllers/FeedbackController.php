<?php

namespace App\Admin\Controllers;

use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use App\Http\Controllers\Controller;

class FeedbackController extends Controller
{
    public function bugs() {
        return view('admin.feedback.bugs-list');
    }

    public function bug(int $bug) {

        $foundBug = SuggestionAndBugs::where('type', FeedbackType::BUG)->where('id', $bug)->first();

        return view('admin.feedback.bug', ['suggestion' => $foundBug]);
    }

    public function suggestions() {
        return view('admin.feedback.suggestions-list');
    }
}
