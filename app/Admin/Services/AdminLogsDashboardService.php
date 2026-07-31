<?php

namespace App\Admin\Services;

use App\Flare\Models\MonitoredLogFileState;
use App\Flare\Models\MonitoredSystemErrorOccurrence;
use App\Flare\Models\MonitoredSystemErrorReport;
use RuntimeException;

class AdminLogsDashboardService
{
    public function __construct(
        private readonly MonitoredBugReportService $monitoredBugReportService,
        private readonly LogReader $logReader,
    ) {}

    public function withLogRoot(string $logRoot): static
    {
        $clone = clone $this;
        $clone->reader = $this->logReader->withLogRoot($logRoot);

        return $clone;
    }

    private ?LogReader $reader = null;

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
        'batch_crafting' => [
            'label' => 'Batch Crafting',
            'patterns' => ['logs/batch-crafting.log', 'logs/batch-crafting-*.log'],
        ],
    ];

    private const LOG_START_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]\s+([A-Za-z0-9_-]+)\.(EMERGENCY|ALERT|CRITICAL|ERROR|FATAL|WARNING|NOTICE|INFO|DEBUG):\s+(.+)$/is';

    private const ERROR_LEVELS = ['emergency', 'alert', 'critical', 'error', 'fatal'];

    private const REQUEST_BYTE_BUDGET = 2097152;

    private const PAGE_SIZE = 50;

    private const READ_CHUNK_BYTES = 65536;

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
            $size = array_reduce($files, fn (int $carry, string $path): int => $carry + (int) $this->reader()->fileSize($path), 0);

            return [
                'key' => $key,
                'label' => self::LOG_CHANNELS[$key]['label'],
                'exists' => count($files) > 0,
                'size_bytes' => $size,
                'files' => array_map(fn (string $path): string => basename($path), $files),
            ];
        }, array_keys(self::LOG_CHANNELS));
    }

    public function entries(string $fileKey, int $page, string $severity, string $dateFrom, string $dateTo, ?string $cursor = null): array
    {
        if (! isset(self::LOG_CHANNELS[$fileKey])) {
            return [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
                'next_cursor' => null,
                'summary' => $this->emptySummary(),
            ];
        }

        $readResult = $this->readCursorEntries($fileKey, $severity, $dateFrom, $dateTo, $cursor);
        $entries = $readResult['entries'];
        $filtered = $this->filter($entries, $severity, $dateFrom, $dateTo);

        $perPage = self::PAGE_SIZE;
        $total = count($filtered);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $pageEntries = array_slice($filtered, ($page - 1) * $perPage, $perPage);
        $nextCursor = $readResult['next_cursor'];

        if (count($filtered) > $perPage && ! empty($pageEntries)) {
            $oldestReturnedEntry = $pageEntries[array_key_last($pageEntries)];
            $nextCursor = $this->encodeCursor(
                (int) $oldestReturnedEntry['_cursor_file_index'],
                (int) $oldestReturnedEntry['_cursor_position'],
            );
        }

        $slice = array_map(
            fn (array $entry): array => $this->compactEntry($entry, $fileKey),
            $pageEntries,
        );

        return [
            'data' => $slice,
            'current_page' => $page,
            'last_page' => $lastPage,
            'total' => $total,
            'next_cursor' => $nextCursor,
            'summary' => $this->summaryFor($filtered),
        ];
    }

    public function entryDetail(string $fileKey, string $detailId): ?array
    {
        if (! isset(self::LOG_CHANNELS[$fileKey])) {
            return null;
        }

        $detailState = $this->decodeOpaqueValue($detailId);
        $fileName = $detailState['file'] ?? null;
        $fingerprint = $detailState['fingerprint'] ?? null;
        $windowEnd = $detailState['window_end'] ?? null;

        if (! is_string($fileName) || ! is_string($fingerprint) || ! is_int($windowEnd)) {
            return null;
        }

        foreach ($this->discoverFiles($fileKey) as $path) {
            if (basename($path) !== $fileName) {
                continue;
            }

            $fileSize = $this->reader()->fileSize($path);

            if ($fileSize === false) {
                return null;
            }

            $read = $this->reader()->readBackward(
                $path,
                min($windowEnd, $fileSize),
                self::REQUEST_BYTE_BUDGET,
            );

            if ($read === false) {
                throw new RuntimeException('Failed to read log detail: '.$fileName);
            }

            foreach ($this->parseContent($read['content'], $path) as $entry) {
                if (hash_equals($fingerprint, $this->entryFingerprint($entry))) {
                    return $entry;
                }
            }
        }

        return null;
    }

    public function poll(string $fileKey, string $severity, string $dateFrom, string $dateTo): array
    {
        if (! isset(self::LOG_CHANNELS[$fileKey])) {
            return [
                'entries' => [],
                'summary' => $this->emptySummary(),
            ];
        }

        $newEntries = [];

        $activePath = $this->activeLogPath($fileKey);

        if (! is_null($activePath)) {
            $newEntries = $this->readNewEntries($fileKey, $activePath);
        }

        usort($newEntries, fn (array $a, array $b): int => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));

        foreach ($newEntries as $entry) {
            if (in_array(strtolower($entry['severity'] ?? ''), self::ERROR_LEVELS, true)) {
                $this->monitoredBugReportService->reportLogEntry($entry);
            }
        }

        $filtered = $this->filter($newEntries, $severity, $dateFrom, $dateTo);

        return [
            'entries' => array_map(
                fn (array $entry): array => $this->compactEntry($entry, $fileKey),
                array_slice($filtered, 0, 50),
            ),
            'summary' => $this->summaryFor($filtered),
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

        $counts = MonitoredSystemErrorOccurrence::query()
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

        return $this->reader()->discoverFiles(self::LOG_CHANNELS[$fileKey]['patterns']);
    }

    private function activeLogPath(string $fileKey): ?string
    {
        return collect($this->discoverFiles($fileKey))
            ->sortByDesc(fn (string $path): array => [
                @filemtime($path) ?: 0,
                basename($path),
            ])
            ->first();
    }

    private function readCursorEntries(
        string $fileKey,
        string $severity,
        string $dateFrom,
        string $dateTo,
        ?string $cursor,
        int $maxBytes = self::REQUEST_BYTE_BUDGET,
    ): array {
        $files = $this->filesForDateRange($fileKey, $dateFrom, $dateTo);
        $cursorState = $this->decodeCursor($cursor);
        $fileIndex = min((int) ($cursorState['file_index'] ?? 0), max(0, count($files) - 1));
        $position = isset($cursorState['position']) ? (int) $cursorState['position'] : null;
        $remainingBudget = min(self::REQUEST_BYTE_BUDGET, max(1, $maxBytes));
        $entries = [];
        $matchingEntryCount = 0;

        while (isset($files[$fileIndex]) && $remainingBudget > 0 && $matchingEntryCount < self::PAGE_SIZE) {
            $path = $files[$fileIndex];
            $fileSize = $this->reader()->fileSize($path);

            if ($fileSize === false || $fileSize === 0) {
                $fileIndex++;
                $position = null;

                continue;
            }

            $endPosition = is_null($position) ? $fileSize : min($position, $fileSize);
            $windowEnd = $endPosition;
            $content = '';
            $contentStart = $endPosition;
            $parsedEntries = [];

            while ($contentStart > 0 && $remainingBudget > 0) {
                $chunkBytes = min(self::READ_CHUNK_BYTES, $remainingBudget, $contentStart);
                $read = $this->reader()->readBackward($path, $contentStart, $chunkBytes);

                if ($read === false) {
                    throw new RuntimeException('Failed to read log file: '.basename($path));
                }

                $content = $read['content'].$content;
                $contentStart = $read['start'];
                $remainingBudget -= $read['end'] - $read['start'];
                $parseableContent = $content;
                $parseableStart = $contentStart;

                if ($contentStart > 0 && preg_match('/(?:^|\n)(?=\[\d{4}-\d{2}-\d{2}[T ])/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $entryOffset = $matches[0][1] + strlen($matches[0][0]);
                    $parseableContent = substr($content, $entryOffset);
                    $parseableStart = $contentStart + $entryOffset;
                }

                $parsedEntries = trim($parseableContent) === ''
                    ? []
                    : $this->parseContent($parseableContent, $path);
                $matchingEntryCount = count($this->filter(
                    array_merge($entries, $parsedEntries),
                    $severity,
                    $dateFrom,
                    $dateTo,
                ));
                $position = $parseableStart;

                if ($matchingEntryCount >= self::PAGE_SIZE) {
                    break;
                }
            }

            foreach ($parsedEntries as $parsedEntry) {
                $parsedEntry['detail_window_end'] = $windowEnd;
                $parsedEntry['_cursor_file_index'] = $fileIndex;
                $parsedEntry['_cursor_position'] = $parseableStart + (int) ($parsedEntry['_byte_start'] ?? 0);
                $entries[] = $parsedEntry;
            }

            if ($position === 0) {
                $fileIndex++;
                $position = null;
            }
        }

        usort($entries, function (array $first, array $second): int {
            $timestampComparison = strcmp($second['timestamp'] ?? '', $first['timestamp'] ?? '');

            if ($timestampComparison !== 0) {
                return $timestampComparison;
            }

            $fileComparison = ((int) $first['_cursor_file_index']) <=> ((int) $second['_cursor_file_index']);

            if ($fileComparison !== 0) {
                return $fileComparison;
            }

            $positionComparison = ((int) $second['_cursor_position']) <=> ((int) $first['_cursor_position']);

            if ($positionComparison !== 0) {
                return $positionComparison;
            }

            return strcmp($this->entryFingerprint($second), $this->entryFingerprint($first));
        });
        $hasMore = isset($files[$fileIndex]);

        return [
            'entries' => $entries,
            'next_cursor' => $hasMore ? $this->encodeCursor($fileIndex, $position) : null,
        ];
    }

    private function filesForDateRange(string $fileKey, string $dateFrom, string $dateTo): array
    {
        $files = array_values(array_filter($this->discoverFiles($fileKey), function (string $path) use ($dateFrom, $dateTo): bool {
            if (! preg_match('/(\d{4}-\d{2}-\d{2})/', basename($path), $matches)) {
                return true;
            }

            $fileDate = $matches[1];

            return ($dateFrom === '' || $fileDate >= $dateFrom)
                && ($dateTo === '' || $fileDate <= $dateTo);
        }));

        usort($files, function (string $first, string $second): int {
            $modifiedComparison = (@filemtime($second) ?: 0) <=> (@filemtime($first) ?: 0);

            return $modifiedComparison !== 0
                ? $modifiedComparison
                : strcmp(basename($second), basename($first));
        });

        return $files;
    }

    private function encodeCursor(int $fileIndex, ?int $position): string
    {
        return rtrim(strtr(base64_encode(json_encode([
            'file_index' => $fileIndex,
            'position' => $position,
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    private function decodeCursor(?string $cursor): array
    {
        if (is_null($cursor) || $cursor === '') {
            return [];
        }

        return $this->decodeOpaqueValue($cursor);
    }

    private function decodeOpaqueValue(string $value): array
    {
        $padding = strlen($value) % 4;
        $encoded = strtr($value, '-_', '+/');

        if ($padding > 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($encoded, true);

        if ($decoded === false) {
            return [];
        }

        $state = json_decode($decoded, true);

        return is_array($state) ? $state : [];
    }

    private function compactEntry(array $entry, string $fileKey): array
    {
        $detailId = $this->encodeOpaqueValue([
            'file' => basename((string) ($entry['file_path'] ?? '')),
            'fingerprint' => $this->entryFingerprint($entry),
            'window_end' => (int) ($entry['detail_window_end'] ?? 0),
        ]);

        unset(
            $entry['stack_trace'],
            $entry['raw_log_entry'],
            $entry['detail_window_end'],
            $entry['_byte_start'],
            $entry['_cursor_file_index'],
            $entry['_cursor_position'],
        );
        $entry['detail_id'] = $detailId;
        $entry['file_key'] = $fileKey;

        return $entry;
    }

    private function entryFingerprint(array $entry): string
    {
        return hash('sha256', implode('|', [
            (string) ($entry['timestamp'] ?? ''),
            (string) ($entry['severity'] ?? ''),
            (string) ($entry['message'] ?? ''),
            (string) ($entry['raw_log_entry'] ?? ''),
        ]));
    }

    private function encodeOpaqueValue(array $value): string
    {
        return rtrim(strtr(base64_encode(json_encode($value, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    private function readNewEntries(string $fileKey, string $path): array
    {
        $fileSize = $this->reader()->fileSize($path);

        if ($fileSize === false) {
            throw new RuntimeException('Failed to stat log file: '.basename($path));
        }

        $state = MonitoredLogFileState::where('channel_key', $fileKey)
            ->where('file_path', $path)
            ->first();

        if (is_null($state)) {
            MonitoredLogFileState::create([
                'channel_key' => $fileKey,
                'file_path' => $path,
                'position' => $fileSize,
                'file_size' => $fileSize,
                'last_scanned_at' => now(),
            ]);

            return [];
        }

        $wasTruncated = $state->position > $fileSize;
        $position = $wasTruncated
            ? max(0, $fileSize - self::REQUEST_BYTE_BUDGET)
            : (int) $state->position;
        $endPosition = min($fileSize, $position + self::REQUEST_BYTE_BUDGET);
        $read = $this->reader()->readBackward(
            $path,
            $endPosition,
            $endPosition - $position,
        );

        if ($read === false) {
            throw new RuntimeException('Failed to read log file: '.basename($path));
        }

        $content = $read['content'];

        if ($wasTruncated && $position > 0) {
            $firstEntry = preg_match(
                '/(?:^|\n)(?=\[\d{4}-\d{2}-\d{2}[T ])/m',
                $content,
                $matches,
                PREG_OFFSET_CAPTURE,
            ) === 1
                ? $matches[0][1] + strlen($matches[0][0])
                : null;

            if (is_null($firstEntry)) {
                $state->update([
                    'position' => $endPosition,
                    'file_size' => $fileSize,
                    'last_scanned_at' => now(),
                ]);

                return [];
            }

            $position += $firstEntry;
            $content = substr($content, $firstEntry);
        }

        $consumedBytes = strlen($content);

        if ($endPosition < $fileSize) {
            $lastEntryOffset = null;

            if (preg_match_all(
                '/(?:^|\n)(?=\[\d{4}-\d{2}-\d{2}[T ])/m',
                $content,
                $matches,
                PREG_OFFSET_CAPTURE,
            ) > 0) {
                $lastMatch = end($matches[0]);
                $lastEntryOffset = $lastMatch[1] + strlen($lastMatch[0]);
            }

            if (! is_null($lastEntryOffset)) {
                $content = substr($content, 0, $lastEntryOffset);
                $consumedBytes = $lastEntryOffset;
            } else {
                $content = '';
                $consumedBytes = 0;
            }
        } elseif ($content !== '' && ! str_ends_with($content, "\n")) {
            $lastNewline = strrpos($content, "\n");

            if ($lastNewline === false) {
                $content = '';
                $consumedBytes = 0;
            } else {
                $content = substr($content, 0, $lastNewline + 1);
                $consumedBytes = $lastNewline + 1;
            }
        }

        $state->update([
            'position' => $position + $consumedBytes,
            'file_size' => $fileSize,
            'last_scanned_at' => now(),
        ]);

        if (trim($content) === '') {
            return [];
        }

        return $this->parseContent($content, $path);
    }

    private function reader(): LogReader
    {
        return $this->reader ?? $this->logReader;
    }

    private function parseContent(string $content, string $path): array
    {
        $entries = [];
        $current = '';
        $currentStart = 0;
        $offset = 0;
        $contentLength = strlen($content);

        while ($offset < $contentLength) {
            $lineStart = $offset;
            $lineEnd = strpos($content, "\n", $offset);
            $line = $lineEnd === false
                ? substr($content, $offset)
                : substr($content, $offset, $lineEnd - $offset);
            $line = rtrim($line, "\r");

            if (preg_match(self::LOG_START_PATTERN, $line) && $current !== '') {
                $entry = $this->parseEntry($current, $path);
                if (! is_null($entry)) {
                    $entry['_byte_start'] = $currentStart;
                    $entries[] = $entry;
                }
                $current = $line;
                $currentStart = $lineStart;
            } else {
                if ($current === '') {
                    $currentStart = $lineStart;
                }

                $current = $current === '' ? $line : $current."\n".$line;
            }

            $offset = $lineEnd === false ? $contentLength : $lineEnd + 1;
        }

        if (trim($current) !== '') {
            $entry = $this->parseEntry($current, $path);
            if (! is_null($entry)) {
                $entry['_byte_start'] = $currentStart;
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
        $lineEnd = strpos($body, "\n");
        $firstLine = $lineEnd === false ? $body : substr($body, 0, $lineEnd);

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
