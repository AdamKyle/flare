<?php

namespace App\Admin\Services;

use RuntimeException;
use Throwable;

class AdminLogsDashboardService
{
    public function __construct(
        private readonly MonitoredBugReportService $monitoredBugReportService,
    ) {}

    private const WHITELISTED_LOG_FILES = [
        'laravel' => 'logs/laravel.log',
        'faction_loyalty' => 'logs/faction-loyalty.log',
        'exploration_automation' => 'logs/exploration-automation.log',
        'capital_city_buildings' => 'logs/capital-city-building-upgrades.log',
        'capital_city_units' => 'logs/capital-city-unit-recruitments.log',
    ];

    private const SEVERITY_PATTERN = '/\.(EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):/i';

    private const SENSITIVE_PATTERNS = [
        '/("?password"?\s*[=:]\s*)"[^"]*"/i',
        '/("?token"?\s*[=:]\s*)"[^"]*"/i',
        '/("?api_key"?\s*[=:]\s*)"[^"]*"/i',
        '/("?secret"?\s*[=:]\s*)"[^"]*"/i',
        '/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/i',
    ];

    public function listFiles(): array
    {
        return array_map(function (string $key, string $path): array {
            $storagePath = storage_path($path);
            return [
                'key' => $key,
                'label' => $this->labelFor($key),
                'exists' => file_exists($storagePath),
                'size_bytes' => file_exists($storagePath) ? filesize($storagePath) : 0,
            ];
        }, array_keys(self::WHITELISTED_LOG_FILES), array_values(self::WHITELISTED_LOG_FILES));
    }

    public function entries(string $fileKey, int $page, string $severity, string $dateFrom, string $dateTo): array
    {
        $path = $this->resolveWhitelistedPath($fileKey);

        if (is_null($path) || ! file_exists($path)) {
            return ['data' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0];
        }

        try {
            $lines = $this->readLines($path);
            $parsed = $this->parseLines($lines);
            $filtered = $this->filter($parsed, $severity, $dateFrom, $dateTo);
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
        $path = $this->resolveWhitelistedPath($fileKey);

        if (is_null($path) || ! file_exists($path)) {
            return $this->emptySummary();
        }

        try {
            $lines = $this->readLines($path);
            $parsed = $this->parseLines($lines);
            $filtered = $this->filter($parsed, $severity, $dateFrom, $dateTo);
        } catch (Throwable $throwable) {
            $this->monitoredBugReportService->reportError(
                'admin-logs-dashboard',
                $throwable->getMessage(),
                ['file_key' => $fileKey, 'severity' => $severity, 'date_from' => $dateFrom, 'date_to' => $dateTo],
                get_class($throwable),
            );

            return $this->emptySummary();
        }

        $bySeverity = [];
        $byDate = [];

        foreach ($filtered as $entry) {
            $sev = strtolower($entry['severity'] ?? 'unknown');
            $bySeverity[$sev] = ($bySeverity[$sev] ?? 0) + 1;

            $date = substr($entry['timestamp'] ?? '', 0, 10);
            if ($date) {
                $byDate[$date] = ($byDate[$date] ?? 0) + 1;
            }
        }

        $chartRows = [];
        foreach ($byDate as $date => $count) {
            $chartRows[] = ['period' => $date, 'count' => $count];
        }
        usort($chartRows, fn ($a, $b) => strcmp($a['period'], $b['period']));

        return [
            'total' => count($filtered),
            'by_severity' => $bySeverity,
            'chart' => $chartRows,
        ];
    }

    private function resolveWhitelistedPath(string $fileKey): ?string
    {
        if (! isset(self::WHITELISTED_LOG_FILES[$fileKey])) {
            return null;
        }

        return storage_path(self::WHITELISTED_LOG_FILES[$fileKey]);
    }

    private function readLines(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Failed to open log file: ' . basename($path));
        }

        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);

        if ($fileSize === 0) {
            fclose($handle);
            return [];
        }

        $maxLines = 10000;
        $chunkSize = 8192;
        $buffer = '';
        $lines = [];
        $pos = $fileSize;

        while ($pos > 0 && count($lines) < $maxLines) {
            $readSize = min($chunkSize, $pos);
            $pos -= $readSize;
            fseek($handle, $pos);
            $chunk = fread($handle, $readSize);

            if ($chunk === false) {
                break;
            }

            $buffer = $chunk . $buffer;
            $split = explode("\n", $buffer);

            $buffer = array_shift($split);

            foreach (array_reverse($split) as $line) {
                $trimmed = rtrim($line);
                if ($trimmed !== '') {
                    $lines[] = $trimmed;
                }
                if (count($lines) >= $maxLines) {
                    break;
                }
            }
        }

        if ($buffer !== '' && count($lines) < $maxLines) {
            $trimmed = rtrim($buffer);
            if ($trimmed !== '') {
                $lines[] = $trimmed;
            }
        }

        fclose($handle);

        return $lines;
    }

    private function parseLines(array $lines): array
    {
        $entries = [];

        foreach ($lines as $line) {
            $entry = $this->parseLine($line);

            if (! is_null($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function parseLine(string $line): ?array
    {
        if (trim($line) === '') {
            return null;
        }

        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]\s+(\w+)\.(EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):\s+(.+?)(\s+\{.*\}|\s+\[.*\])?\s*$/is';

        if (preg_match($pattern, $line, $matches)) {
            $message = $this->redactSensitive(trim($matches[4]));
            $context = isset($matches[5]) ? $this->redactSensitive(trim($matches[5])) : null;

            return [
                'timestamp' => $matches[1],
                'channel' => $matches[2],
                'severity' => strtolower($matches[3]),
                'message' => $message,
                'context' => $context,
                'raw_parseable' => true,
            ];
        }

        return [
            'timestamp' => null,
            'channel' => null,
            'severity' => 'unknown',
            'message' => $this->redactSensitive(substr($line, 0, 500)),
            'context' => null,
            'raw_parseable' => false,
        ];
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

    private function redactSensitive(string $text): string
    {
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            $text = preg_replace($pattern, '$1"[REDACTED]"', $text) ?? $text;
        }

        return $text;
    }

    private function labelFor(string $key): string
    {
        return match ($key) {
            'laravel' => 'Laravel (default)',
            'faction_loyalty' => 'Faction Loyalty',
            'exploration_automation' => 'Exploration Automation',
            'capital_city_buildings' => 'Capital City Buildings',
            'capital_city_units' => 'Capital City Units',
            default => $key,
        };
    }

    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'by_severity' => [],
            'chart' => [],
        ];
    }
}
