<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class AdminLogPollTransformer extends BaseTransformer
{
    public function __construct(
        private readonly AdminLogEntryTransformer $entryTransformer,
        private readonly AdminLogSummaryTransformer $summaryTransformer,
        private readonly AdminLogFileTransformer $fileTransformer,
        private readonly AdminBugReportTransformer $bugReportTransformer,
        private readonly AdminBugChartTransformer $bugChartTransformer,
    ) {}

    public function transform(array $poll): array
    {
        return [
            'entries' => array_map(fn (array $entry): array => $this->entryTransformer->transform($entry), $poll['entries']),
            'summary' => $this->summaryTransformer->transform($poll['summary']),
            'files' => array_map(fn (array $file): array => $this->fileTransformer->transform($file), $poll['files']),
            'bugs' => array_map(fn (array $bug): array => $this->bugReportTransformer->transform($bug), $poll['bugs']),
            'bug_chart' => array_map(fn (array $row): array => $this->bugChartTransformer->transform($row), $poll['bug_chart']),
        ];
    }
}
