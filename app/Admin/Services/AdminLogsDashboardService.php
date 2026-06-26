<?php

namespace App\Admin\Services;

use App\Flare\Models\MonitoredLogFileState;
use App\Flare\Models\MonitoredSystemErrorReport;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

class AdminLogsDashboardService
{
    private ?string $logRootOverride = null;

    public function __construct(
        private readonly MonitoredBugReportService $monitoredBugReportService,
    ) {}

    public function withLogRoot(string $logRoot): static
    {
        $clone = clone $this;
        $clone->logRootOverride = rtrim($logRoot, '/');
        return $clone;
    }

    private function resolveLogPattern(string $pattern): string
    {
        if ($this->logRootOverride !== null) {
            $relative = preg_replace('#^logs/#', '', $pattern);
            return $this->logRootOverride . '/' . $relative;
        }
        return storage_path($pattern);
    }

    private const LOG_CHANNELS = [
        'laravel' => [
            'label' => 'Laravel (default)',
            'patterns' => ['logs/laravel.log', 'logs/laravel-*.log'],
        ],
        'faction_loyalty' => [
            'label' => 'Faction Loyalty',
            'patterns' => ['logs/faction-loyalty.log', 'logs/faction-loyalty-*.log'],
        ],
        'exploration_automation' => [
            'label' => 'Exploration Automation',
            'patterns' => ['logs/exploration-automation.log', 'logs/exploration-automation-*.log'],
        ],
        'capital_city' => [
            'label' => 'Capital City',
            'patterns' => [
                'logs/capital-city-building-upgrades.log',
                'logs/capital-city-building-upgrades-*.log',
                'logs/capital-city-unit-recruitments.log',
                'logs/capital-city-unit-recruitments-*.log',
            ],
        ],
        'reward_processing' => [
            'label' => 'Reward Processing',
            'patterns' => ['logs/reward_processing.log', 'logs/reward_processing-*.log'],
        ],
        'reward_ledger' => [
            'label' => 'Reward Ledger',
            'patterns' => ['logs/reward_ledger.log', 'logs/reward_ledger-*.log'],
        ],
    ];

    private const LOG_START_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]\s+([A-Za-z0-9_-]+)\.(EMERGENCY|ALERT|CRITICAL|ERROR|FATAL|WARNING|NOTICE|INFO|DEBUG):\s+(.+)$/is';

    private const ERROR_LEVELS = ['emergency', 'alert', 'critical', 'error', 'fatal'];

    private const SENSITIVE_PATTERNS = [
        '/("?password"?\s*[=:]\s*)"[^"]*"/i',
        '/("?token"?\s*[=:]\s*)"[^"]*"/i',
        '/("?api_key"?\s*[=:]\s*)"[^"]*"/i',
        '/("?secret"?\s*[=:]\s*)"[^"]*"/i',
        '/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/i',
    ];

    public function listFiles(): array
    {
        return array_map(function (string $key): array {
            $files = $this->discoverFiles($key);
            $size = array_reduce($files, fn (int $carry, string $path): int => $carry + (int) filesize($path), 0);

            return [
                'key' => $key,
                'label' => self::LOG_CHANNELS[$key]['label'],
                'exists' => count($files) > 0,
                'size_bytes' => $size,
                'files' => array_map(fn (string $path): string => basename($path), $files),
            ];
        }, array_keys(self::LOG_CHANNELS));
    }

    public function entries(string $fileKey, int $page, string $severity, string $dateFrom, string $dateTo): array
    {
        if (! isset(self::LOG_CHANNELS[$fileKey])) {
            return ['data' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0];
        }

        try {
            $entries = $this->readBoundedEntries($fileKey);
            $filtered = $this->filter($entries, $severity, $dateFrom, $dateTo);
        } catch (Throwable $throwable) {
            $this->monitoredBugReportService->reportError(
                'admin-logs-dashboard',
                $throwable->getMessage(),
                ['file_key' => $fileKey, 'severity' => $severity, 'date_from' => $dateFrom, 'date_to' => $dateTo],
                get_class($throwable),
            );

            return ['data' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0];
        }

        $perPage = 50;
        $total = count($filtered);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $slice = array_slice($filtered, ($page - 1) * $perPage, $perPage);

        return [
            'data' => $slice,
            'current_page' => $page,
            'last_page' => $lastPage,
            'total' => $total,
        ];
    }

    public function summary(string $fileKey, string $severity, string $dateFrom, string $dateTo): array
    {
        if (! isset(self::LOG_CHANNELS[$fileKey])) {
            return $this->emptySummary();
        }

        try {
            $entries = $this->readBoundedEntries($fileKey);
            $filtered = $this->filter($entries, $severity, $dateFrom, $dateTo);
        } catch (Throwable $throwable) {
            $this->monitoredBugReportService->reportError(
                'admin-logs-dashboard',
                $throwable->getMessage(),
                ['file_key' => $fileKey, 'severity' => $severity, 'date_from' => $dateFrom, 'date_to' => $dateTo],
                get_class($throwable),
            );

            return $this->emptySummary();
        }

        return $this->summaryFor($filtered);
    }

    public function poll(string $fileKey, string $severity, string $dateFrom, string $dateTo): array
    {
        if (! isset(self::LOG_CHANNELS[$fileKey])) {
            return [
                'entries' => [],
                'summary' => $this->emptySummary(),
                'files' => $this->listFiles(),
                'bugs' => $this->bugReports(),
                'bug_chart' => $this->bugChart(30),
            ];
        }

        $newEntries = [];

        foreach ($this->discoverFiles($fileKey) as $path) {
            $newEntries = array_merge($newEntries, $this->readNewEntries($fileKey, $path));
        }

        usort($newEntries, fn (array $a, array $b): int => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));

        foreach ($newEntries as $entry) {
            if (in_array(strtolower($entry['severity'] ?? ''), self::ERROR_LEVELS, true)) {
                $this->monitoredBugReportService->reportLogEntry($entry);
            }
        }

        $allEntries = $this->readBoundedEntries($fileKey);
        $filtered = $this->filter($allEntries, $severity, $dateFrom, $dateTo);

        return [
            'entries' => array_slice($this->filter($newEntries, $severity, $dateFrom, $dateTo), 0, 50),
            'summary' => $this->summaryFor($filtered),
            'files' => $this->listFiles(),
            'bugs' => $this->bugReports(),
            'bug_chart' => $this->bugChart(30),
        ];
    }

    public function bugChart(int $days): array
    {
        $days = in_array($days, [7, 14, 30, 60, 120], true) ? $days : 30;
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = [];

        for ($index = 0; $index < $days; $index++) {
            $date = $start->copy()->addDays($index)->toDateString();
            $rows[$date] = ['period' => $date, 'occurrences' => 0];
        }

        $counts = \App\Flare\Models\MonitoredSystemErrorOccurrence::query()
            ->selectRaw('DATE(occurred_at) as period, COUNT(*) as aggregate')
            ->where('occurred_at', '>=', $start)
            ->groupBy('period')
            ->pluck('aggregate', 'period');

        foreach ($counts as $period => $count) {
            if (isset($rows[$period])) {
                $rows[$period]['occurrences'] = (int) $count;
            }
        }

        return array_values($rows);
    }

    public function bugReports(): array
    {
        return MonitoredSystemErrorReport::query()
            ->with(['occurrences' => fn ($query) => $query->latest('occurred_at')->limit(10)])
            ->latest('last_seen_at')
            ->limit(50)
            ->get()
            ->map(function (MonitoredSystemErrorReport $report): array {
                return [
                    'id' => $report->id,
                    'fingerprint' => $report->fingerprint,
                    'title' => $report->title,
                    'status' => $report->status,
                    'severity' => $report->severity,
                    'first_seen_at' => $report->first_seen_at?->toDateTimeString(),
                    'last_seen_at' => $report->last_seen_at?->toDateTimeString(),
                    'occurrence_count' => $report->occurrence_count,
                    'latest_message' => $report->latest_message,
                    'latest_stack_trace' => $report->latest_stack_trace,
                    'latest_raw_log_entry' => $report->latest_raw_log_entry,
                    'occurrences' => $report->occurrences
                        ->map(fn ($occurrence): array => [
                            'occurred_at' => $occurrence->occurred_at?->toDateTimeString(),
                            'level' => $occurrence->level,
                            'channel' => $occurrence->channel,
                            'file_path' => $occurrence->file_path,
                            'message' => $occurrence->message,
                            'exception_class' => $occurrence->exception_class,
                            'exception_file' => $occurrence->exception_file,
                            'exception_line' => $occurrence->exception_line,
                            'stack_trace' => $occurrence->stack_trace,
                            'raw_log_entry' => $occurrence->raw_log_entry,
                            'context' => $occurrence->context,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    private function discoverFiles(string $fileKey): array
    {
        if (! isset(self::LOG_CHANNELS[$fileKey])) {
            return [];
        }

        $files = [];

        foreach (self::LOG_CHANNELS[$fileKey]['patterns'] as $pattern) {
            $matches = glob($this->resolveLogPattern($pattern)) ?: [];
            foreach ($matches as $path) {
                if (is_file($path) && is_readable($path)) {
                    $files[$path] = $path;
                }
            }
        }

        ksort($files);

        return array_values($files);
    }

    private function readBoundedEntries(string $fileKey, int $maxBytes = 2097152): array
    {
        $entries = [];

        foreach ($this->discoverFiles($fileKey) as $path) {
            $fileSize = filesize($path);

            if ($fileSize === false || $fileSize === 0) {
                continue;
            }

            $offset = max(0, $fileSize - $maxBytes);
            $handle = fopen($path, 'rb');

            if ($handle === false) {
                continue;
            }

            if ($offset > 0) {
                fseek($handle, $offset);
                fgets($handle);
            }

            $content = stream_get_contents($handle);
            fclose($handle);

            if ($content === false || trim($content) === '') {
                continue;
            }

            $entries = array_merge($entries, $this->parseContent($content, $path));
        }

        usort($entries, fn (array $a, array $b): int => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));

        return $entries;
    }

    private function readChannelEntries(string $fileKey): array
    {
        $entries = [];

        foreach ($this->discoverFiles($fileKey) as $path) {
            $entries = array_merge($entries, $this->parseContent((string) file_get_contents($path), $path));
        }

        usort($entries, fn (array $a, array $b): int => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));

        return $entries;
    }

    private function readNewEntries(string $fileKey, string $path): array
    {
        $fileSize = filesize($path);
        $state = MonitoredLogFileState::firstOrCreate(
            ['channel_key' => $fileKey, 'file_path' => $path],
            ['position' => 0, 'file_size' => 0],
        );

        $position = $state->position > $fileSize ? 0 : $state->position;
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Failed to open log file: ' . basename($path));
        }

        fseek($handle, $position);
        $content = stream_get_contents($handle);
        fclose($handle);

        $state->update([
            'position' => $fileSize,
            'file_size' => $fileSize,
            'last_scanned_at' => now(),
        ]);

        if ($content === false || trim($content) === '') {
            return [];
        }

        return $this->parseContent($content, $path);
    }

    private function parseContent(string $content, string $path): array
    {
        $entries = [];
        $current = '';

        foreach (preg_split('/\R/', $content) as $line) {
            if (preg_match(self::LOG_START_PATTERN, $line) && $current !== '') {
                $entry = $this->parseEntry($current, $path);
                if (! is_null($entry)) {
                    $entries[] = $entry;
                }
                $current = $line;
            } else {
                $current = $current === '' ? $line : $current . "\n" . $line;
            }
        }

        if (trim($current) !== '') {
            $entry = $this->parseEntry($current, $path);
            if (! is_null($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function parseEntry(string $raw, string $path): ?array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        if (! preg_match(self::LOG_START_PATTERN, $raw, $matches)) {
            return [
                'timestamp' => null,
                'channel' => null,
                'severity' => 'unknown',
                'message' => $this->redactSensitive(substr($raw, 0, 500)),
                'context' => null,
                'context_payload' => null,
                'exception_class' => null,
                'exception_file' => null,
                'exception_line' => null,
                'stack_trace' => null,
                'raw_log_entry' => $this->redactSensitive($raw),
                'file_path' => $path,
                'raw_parseable' => false,
            ];
        }

        $body = trim($matches[4]);
        $contextPayload = $this->extractContextPayload($body);
        $message = $this->extractMessage($body);
        $exception = $this->extractExceptionDetails($body, $raw);

        return [
            'timestamp' => $matches[1],
            'channel' => $matches[2],
            'severity' => strtolower($matches[3]),
            'message' => $this->redactSensitive($message),
            'context' => is_null($contextPayload) ? null : $this->redactSensitive(json_encode($contextPayload) ?: ''),
            'context_payload' => $this->redactArray($contextPayload),
            'exception_class' => $exception['class'],
            'exception_file' => $exception['file'],
            'exception_line' => $exception['line'],
            'stack_trace' => $exception['stack'],
            'raw_log_entry' => $this->redactSensitive($raw),
            'file_path' => $path,
            'raw_parseable' => true,
        ];
    }

    private function extractContextPayload(string $body): ?array
    {
        $candidate = null;

        if (preg_match('/(\{.*\})\s*$/s', $body, $matches)) {
            $candidate = $matches[1];
        }

        if (is_null($candidate)) {
            return null;
        }

        $decoded = json_decode($candidate, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function extractMessage(string $body): string
    {
        $firstLine = strtok($body, "\n");

        if ($firstLine === false) {
            return '';
        }

        $firstLine = preg_replace('/\s+\{.*\}\s*$/s', '', $firstLine) ?? $firstLine;

        return trim($firstLine);
    }

    private function extractExceptionDetails(string $body, string $raw): array
    {
        $class = null;
        $file = null;
        $line = null;
        $stack = null;

        if (preg_match('/"exception":"([^"]+)"/', $body, $matches)) {
            $class = $matches[1];
        }

        if (preg_match('/(Exception|Error):\s*(.*?)\s+in\s+([^:\s]+):(\d+)/s', $body, $matches)) {
            $class = $class ?? $matches[1];
            $file = $matches[3];
            $line = (int) $matches[4];
        }

        if (preg_match('/"file":"([^"]+)"/', $body, $matches)) {
            $file = $file ?? stripcslashes($matches[1]);
        }

        if (preg_match('/"line":(\d+)/', $body, $matches)) {
            $line = $line ?? (int) $matches[1];
        }

        if (preg_match('/(#0\s+.*)$/s', $raw, $matches)) {
            $stack = $this->redactSensitive(trim($matches[1]));
        } elseif (preg_match('/"trace":(\[.*\])\s*$/s', $body, $matches)) {
            $stack = $this->redactSensitive($matches[1]);
        }

        return ['class' => $class, 'file' => $file, 'line' => $line, 'stack' => $stack];
    }

    private function filter(array $entries, string $severity, string $dateFrom, string $dateTo): array
    {
        return array_values(array_filter($entries, function (array $entry) use ($severity, $dateFrom, $dateTo): bool {
            if ($severity !== '' && strtolower($entry['severity'] ?? '') !== strtolower($severity)) {
                return false;
            }

            $timestamp = substr($entry['timestamp'] ?? '', 0, 10);

            if ($dateFrom !== '' && $timestamp !== '' && $timestamp < $dateFrom) {
                return false;
            }

            if ($dateTo !== '' && $timestamp !== '' && $timestamp > $dateTo) {
                return false;
            }

            return true;
        }));
    }

    private function summaryFor(array $entries): array
    {
        $bySeverity = [];
        $byDate = [];

        foreach ($entries as $entry) {
            $severity = strtolower($entry['severity'] ?? 'unknown');
            $bySeverity[$severity] = ($bySeverity[$severity] ?? 0) + 1;

            $date = substr($entry['timestamp'] ?? '', 0, 10);
            if ($date) {
                $byDate[$date] = ($byDate[$date] ?? 0) + 1;
            }
        }

        $chartRows = [];
        foreach ($byDate as $date => $count) {
            $chartRows[] = ['period' => $date, 'count' => $count];
        }
        usort($chartRows, fn (array $a, array $b): int => strcmp($a['period'], $b['period']));

        return [
            'total' => count($entries),
            'by_severity' => $bySeverity,
            'chart' => $chartRows,
        ];
    }

    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'by_severity' => [],
            'chart' => [],
        ];
    }

    private function redactSensitive(string $text): string
    {
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            $text = preg_replace($pattern, '$1"[REDACTED]"', $text) ?? $text;
        }

        return $text;
    }

    private function redactArray(?array $context): ?array
    {
        if (is_null($context)) {
            return null;
        }

        $redacted = [];

        foreach ($context as $key => $value) {
            if (str_contains(strtolower((string) $key), 'password') || str_contains(strtolower((string) $key), 'token')) {
                $redacted[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactArray($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }
}
