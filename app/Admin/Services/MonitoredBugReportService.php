<?php

namespace App\Admin\Services;

use App\Flare\Models\MonitoredSystemErrorOccurrence;
use App\Flare\Models\MonitoredSystemErrorReport;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use Carbon\Carbon;
use Throwable;

class MonitoredBugReportService
{
    private const SENSITIVE_KEYS = ['password', 'token', 'api_key', 'secret', 'authorization'];

    public function reportError(
        string $sourceSystem,
        string $normalizedMessage,
        array $context = [],
        ?string $exceptionClass = null,
        ?int $characterId = null,
        ?string $sourceId = null,
    ): void {
        try {
            $fingerprint = $this->fingerprint($sourceSystem, $exceptionClass, $normalizedMessage, null, null, $sourceId);
            $title = $this->buildTitle($sourceSystem, $normalizedMessage);

            $this->storeSystemOccurrence([
                'fingerprint' => $fingerprint,
                'title' => $title,
                'level' => 'error',
                'channel' => $sourceSystem,
                'message' => $normalizedMessage,
                'exception_class' => $exceptionClass,
                'context' => $context,
                'occurred_at' => now(),
                'environment' => config('app.env'),
            ]);

            $existing = SuggestionAndBugs::where('type', FeedbackType::BUG)
                ->where('description', 'like', '%Fingerprint: ' . $fingerprint . '%')
                ->first();

            if (! is_null($existing)) {
                return;
            }

            SuggestionAndBugs::create([
                'character_id' => $characterId,
                'title' => $title,
                'type' => FeedbackType::BUG,
                'platform' => 'system',
                'description' => $this->buildDescription(
                    $sourceSystem,
                    $normalizedMessage,
                    $exceptionClass,
                    $sourceId,
                    $this->redactContext($context),
                    $fingerprint,
                ),
                'uploaded_image_paths' => [],
            ]);
        } catch (Throwable) {
        }
    }

    public function reportLogEntry(array $entry): ?MonitoredSystemErrorReport
    {
        try {
            $message = (string) ($entry['message'] ?? '');
            $exceptionClass = $entry['exception_class'] ?? null;
            $file = $entry['exception_file'] ?? null;
            $line = $entry['exception_line'] ?? null;
            $stackTrace = $entry['stack_trace'] ?? null;
            $environment = config('app.env');
            $fingerprint = $this->fingerprint(
                (string) ($entry['channel'] ?? 'log'),
                is_string($exceptionClass) ? $exceptionClass : null,
                $message,
                is_string($file) ? $file : null,
                is_int($line) ? $line : null,
                $this->topStackFrame(is_string($stackTrace) ? $stackTrace : null),
                $environment,
            );

            return $this->storeSystemOccurrence([
                'fingerprint' => $fingerprint,
                'title' => $this->buildTitle((string) ($entry['channel'] ?? 'log'), $message),
                'level' => $entry['severity'] ?? null,
                'channel' => $entry['channel'] ?? null,
                'file_path' => $entry['file_path'] ?? null,
                'message' => $message,
                'exception_class' => $exceptionClass,
                'exception_file' => $file,
                'exception_line' => $line,
                'stack_trace' => $stackTrace,
                'raw_log_entry' => $entry['raw_log_entry'] ?? null,
                'user_id' => $this->contextValue($entry['context_payload'] ?? null, 'user_id'),
                'request_path' => $this->contextValue($entry['context_payload'] ?? null, 'request_path'),
                'job_class' => $this->contextValue($entry['context_payload'] ?? null, 'job_class'),
                'queue' => $this->contextValue($entry['context_payload'] ?? null, 'queue'),
                'context' => is_array($entry['context_payload'] ?? null) ? $entry['context_payload'] : null,
                'occurred_at' => $this->occurredAt($entry['timestamp'] ?? null),
                'environment' => $environment,
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    private function storeSystemOccurrence(array $capture): MonitoredSystemErrorReport
    {
        $occurredAt = $capture['occurred_at'] instanceof Carbon ? $capture['occurred_at'] : now();
        $report = MonitoredSystemErrorReport::firstOrCreate(
            ['fingerprint' => $capture['fingerprint']],
            [
                'title' => $capture['title'],
                'status' => 'open',
                'severity' => $capture['level'] ?? null,
                'environment' => $capture['environment'] ?? null,
                'exception_class' => $capture['exception_class'] ?? null,
                'latest_message' => $capture['message'] ?? null,
                'latest_stack_trace' => $capture['stack_trace'] ?? null,
                'latest_raw_log_entry' => $capture['raw_log_entry'] ?? null,
                'occurrence_count' => 0,
                'first_seen_at' => $occurredAt,
                'last_seen_at' => $occurredAt,
            ],
        );

        MonitoredSystemErrorOccurrence::create([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => $occurredAt,
            'level' => $capture['level'] ?? null,
            'channel' => $capture['channel'] ?? null,
            'file_path' => $capture['file_path'] ?? null,
            'message' => $capture['message'] ?? null,
            'exception_class' => $capture['exception_class'] ?? null,
            'exception_file' => $capture['exception_file'] ?? null,
            'exception_line' => $capture['exception_line'] ?? null,
            'stack_trace' => $capture['stack_trace'] ?? null,
            'raw_log_entry' => $capture['raw_log_entry'] ?? null,
            'user_id' => $capture['user_id'] ?? null,
            'request_path' => $capture['request_path'] ?? null,
            'job_class' => $capture['job_class'] ?? null,
            'queue' => $capture['queue'] ?? null,
            'environment' => $capture['environment'] ?? null,
            'context' => $this->redactContext(is_array($capture['context'] ?? null) ? $capture['context'] : []),
        ]);

        $report->update([
            'severity' => $capture['level'] ?? $report->severity,
            'environment' => $capture['environment'] ?? $report->environment,
            'exception_class' => $capture['exception_class'] ?? $report->exception_class,
            'latest_message' => $capture['message'] ?? $report->latest_message,
            'latest_stack_trace' => $capture['stack_trace'] ?? $report->latest_stack_trace,
            'latest_raw_log_entry' => $capture['raw_log_entry'] ?? $report->latest_raw_log_entry,
            'occurrence_count' => $report->occurrence_count + 1,
            'first_seen_at' => $report->first_seen_at ?? $occurredAt,
            'last_seen_at' => $occurredAt,
        ]);

        return $report->refresh();
    }

    private function fingerprint(
        string $sourceSystem,
        ?string $exceptionClass,
        string $message,
        ?string $file = null,
        ?int $line = null,
        ?string $sourceId = null,
        ?string $environment = null,
    ): string {
        return md5(implode('|', [
            $sourceSystem,
            $exceptionClass ?? '',
            $message,
            $file ?? '',
            (string) ($line ?? ''),
            $sourceId ?? '',
            $environment ?? '',
        ]));
    }

    private function buildTitle(string $sourceSystem, string $message): string
    {
        $short = substr($message, 0, 80);

        return '[Auto] ' . $sourceSystem . ': ' . $short;
    }

    private function buildDescription(
        string $sourceSystem,
        string $message,
        ?string $exceptionClass,
        ?string $sourceId,
        array $context,
        string $fingerprint,
    ): string {
        $lines = [
            'Source System: ' . $sourceSystem,
            'Message: ' . $message,
        ];

        if (! is_null($exceptionClass)) {
            $lines[] = 'Exception: ' . $exceptionClass;
        }

        if (! is_null($sourceId)) {
            $lines[] = 'Source ID: ' . $sourceId;
        }

        if (! empty($context)) {
            $lines[] = 'Context: ' . json_encode($context);
        }

        $lines[] = 'Fingerprint: ' . $fingerprint;
        $lines[] = 'Auto-generated: ' . now()->toDateTimeString();

        return implode("\n", $lines);
    }

    private function redactContext(array $context): array
    {
        $redacted = [];

        foreach ($context as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $redacted[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactContext($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    private function isSensitiveKey(string $key): bool
    {
        $lower = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($lower, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function topStackFrame(?string $stackTrace): ?string
    {
        if (is_null($stackTrace)) {
            return null;
        }

        $line = strtok($stackTrace, "\n");

        return $line === false ? null : trim($line);
    }

    private function contextValue(mixed $context, string $key): mixed
    {
        if (! is_array($context) || ! array_key_exists($key, $context)) {
            return null;
        }

        return $context[$key];
    }

    private function occurredAt(mixed $timestamp): Carbon
    {
        if (! is_string($timestamp) || $timestamp === '') {
            return now();
        }

        try {
            return Carbon::parse($timestamp);
        } catch (Throwable) {
            return now();
        }
    }
}
