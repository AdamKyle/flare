<?php

namespace App\Admin\Services;

use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;

class FeedbackService {

    public function gatherFeedbackData(): array {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $bugsCountToday = SuggestionAndBugs::where('type', FeedbackType::BUG)->where('created_at', '>=', $today)->count();
        $bugsCountYesterday = SuggestionAndBugs::where('type', FeedbackType::BUG)->where('created_at', '>=', $yesterday)->where('created_at', '<', $today)->count();

        $suggestionCountToday = SuggestionAndBugs::where('type', FeedbackType::SUGGESTION)->where('created_at', '>=', $today)->count();
        $suggestionCountYesterday = SuggestionAndBugs::where('type', FeedbackType::SUGGESTION)->where('created_at', '>=', $yesterday)->where('created_at', '<', $today)->count();

        $bugsDifference = $this->calculateDifference($bugsCountToday, $bugsCountYesterday);
        $suggestionDifference = $this->calculateDifference($suggestionCountToday, $suggestionCountYesterday);

        return [
            'bugsCount' => $bugsCountToday,
            'bugsDifference' => $bugsDifference,
            'suggestionCount' => $suggestionCountToday,
            'suggestionDifference' => $suggestionDifference,
        ];

    }

    private function calculateDifference($todayCount, $yesterdayCount): float {
        if ($yesterdayCount == 0) {
            return $todayCount == 0 ? 0 : 100;
        }

        $difference = (($todayCount - $yesterdayCount) / $yesterdayCount) * 100;

        return round($difference, 2);
    }
}
