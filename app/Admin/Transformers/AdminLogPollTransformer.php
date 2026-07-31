<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class AdminLogPollTransformer extends BaseTransformer
{
    public function __construct(
        private readonly AdminLogEntryTransformer $entryTransformer,
        private readonly AdminLogSummaryTransformer $summaryTransformer,
    ) {}

    public function transform(array $poll): array
    {
        return [
            'entries' => array_map(fn (array $entry): array => $this->entryTransformer->transform($entry), $poll['entries']),
            'summary' => $this->summaryTransformer->transform($poll['summary']),
        ];
    }
}
