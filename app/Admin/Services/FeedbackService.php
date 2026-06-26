<?php

namespace App\Admin\Services;

use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;

class FeedbackService {

    public function gatherFeedbackData(): array {
        $totalBugCount = SuggestionAndBugs::where('type', FeedbackType::BUG)->count();
        $totalSuggestionCount = SuggestionAndBugs::where('type', FeedbackType::SUGGESTION)->count();
        $periodStart = now()->subDays(7);

        $bugsInPeriod = SuggestionAndBugs::where('type', FeedbackType::BUG)
            ->where('created_at', '>=', $periodStart)
            ->count();
        $suggestionsInPeriod = SuggestionAndBugs::where('type', FeedbackType::SUGGESTION)
            ->where('created_at', '>=', $periodStart)
            ->count();
        $periodTotal = $bugsInPeriod + $suggestionsInPeriod;

        return [
            'bugsCount' => $totalBugCount,
            'bugsPercentage' => $this->calculateShare($bugsInPeriod, $periodTotal),
            'suggestionCount' => $totalSuggestionCount,
            'suggestionPercentage' => $this->calculateShare($suggestionsInPeriod, $periodTotal),
        ];
    }

    private function calculateShare(int $count, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($count / $total) * 100, 2);
    }
}
