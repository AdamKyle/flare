<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class AdminLogEntryTransformer extends BaseTransformer
{
    public function transform(array $entry): array
    {
        return [
            'timestamp' => $entry['timestamp'] ?? null,
            'channel' => $entry['channel'] ?? null,
            'severity' => $entry['severity'] ?? 'unknown',
            'message' => $entry['message'] ?? '',
            'context' => $entry['context'] ?? null,
            'exception_class' => $entry['exception_class'] ?? null,
            'exception_file' => $entry['exception_file'] ?? null,
            'exception_line' => $entry['exception_line'] ?? null,
            'stack_trace' => $entry['stack_trace'] ?? null,
            'raw_log_entry' => $entry['raw_log_entry'] ?? null,
            'file_path' => $entry['file_path'] ?? null,
            'raw_parseable' => (bool) ($entry['raw_parseable'] ?? false),
        ];
    }
}
