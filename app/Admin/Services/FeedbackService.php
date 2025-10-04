<?php

namespace App\Admin\Services;

use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;

class FeedbackService
{
    public function gatherFeedbackData(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $totalBugCount = SuggestionAndBugs::where('type', FeedbackType::BUG)->count();
        $totalSuggestionCount = SuggestionAndBugs::where('type', FeedbackType::SUGGESTION)->count();

        $bugsCountToday = SuggestionAndBugs::where('type', FeedbackType::BUG)->where('created_at', '>=', $today)->count();
        $bugsCountYesterday = SuggestionAndBugs::where('type', FeedbackType::BUG)->where('created_at', '>=', $yesterday)->where('created_at', '<', $today)->count();

        $suggestionCountToday = SuggestionAndBugs::where('type', FeedbackType::SUGGESTION)->where('created_at', '>=', $today)->count();
        $suggestionCountYesterday = SuggestionAndBugs::where('type', FeedbackType::SUGGESTION)->where('created_at', '>=', $yesterday)->where('created_at', '<', $today)->count();

        $bugsDifference = $this->calculatePercentageDifference($bugsCountToday, $bugsCountYesterday);
        $suggestionDifference = $this->calculatePercentageDifference($suggestionCountToday, $suggestionCountYesterday);

        return [
            'bugsCount' => $totalBugCount,
            'bugsDifference' => abs($bugsDifference),
            'suggestionCount' => $totalSuggestionCount,
            'suggestionDifference' => abs($suggestionDifference),
        ];
    }

    private function calculatePercentageDifference(int $countToday, int $countYesterday): float
    {
        if ($countYesterday === 0) {
            return $countToday > 0 ? ($countToday / 100) : 0.0;
        }

        $difference = $countToday - $countYesterday;
        $percentageDifference = ($difference / $countYesterday) * 100;

        return $percentageDifference;
    }
}
