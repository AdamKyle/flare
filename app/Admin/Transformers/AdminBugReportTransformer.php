<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class AdminBugReportTransformer extends BaseTransformer
{
    public function transform(array $report): array
    {
        return [
            'id' => (int) $report['id'],
            'fingerprint' => $report['fingerprint'],
            'title' => $report['title'],
            'status' => $report['status'],
            'severity' => $report['severity'],
            'first_seen_at' => $report['first_seen_at'],
            'last_seen_at' => $report['last_seen_at'],
            'occurrence_count' => (int) $report['occurrence_count'],
            'latest_message' => $report['latest_message'],
            'latest_stack_trace' => $report['latest_stack_trace'],
            'latest_raw_log_entry' => $report['latest_raw_log_entry'],
            'occurrences' => array_map(fn (array $occurrence): array => [
                'occurred_at' => $occurrence['occurred_at'],
                'level' => $occurrence['level'],
                'channel' => $occurrence['channel'],
                'file_path' => $occurrence['file_path'],
                'message' => $occurrence['message'],
                'exception_class' => $occurrence['exception_class'],
                'exception_file' => $occurrence['exception_file'],
                'exception_line' => $occurrence['exception_line'],
                'stack_trace' => $occurrence['stack_trace'],
                'raw_log_entry' => $occurrence['raw_log_entry'],
                'context' => $occurrence['context'],
            ], $report['occurrences']),
        ];
    }
}
