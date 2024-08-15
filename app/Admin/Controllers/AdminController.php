<?php

namespace App\Admin\Controllers;

use App\Admin\Services\FeedbackService;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{

    public function __construct(private readonly FeedbackService $feedbackService){
    }

    public function home() {

        return view('admin.home', $this->feedbackService->gatherFeedbackData());
    }

    public function chatLogs()
    {
        return view('admin.chat.logs');
    }
}
