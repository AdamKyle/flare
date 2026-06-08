<?php

namespace Tests\Traits;

use App\Flare\Models\SuggestionAndBugs;

trait CreateSuggestionAndBugs
{
    public function createSuggestionAndBug(array $options = []): SuggestionAndBugs
    {
        return SuggestionAndBugs::factory()->create($options);
    }
}
