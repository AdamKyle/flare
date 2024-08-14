<?php

namespace App\Admin\Controllers;

use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function home()
    {

        $bugsCount = SuggestionAndBugs::where('type', FeedbackType::BUG)->count();
        $suggestionCount = SuggestionAndBugs::where('type', FeedbackType::SUGGESTION)->count();

        return view('admin.home', [
            'bugsCount' => $bugsCount,
            'suggestionCount' => $suggestionCount,
        ]);
    }

    public function chatLogs()
    {
        return view('admin.chat.logs');
    }
}
