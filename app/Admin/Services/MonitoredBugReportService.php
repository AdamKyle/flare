<?php

namespace App\Admin\Services;

use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
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
            $fingerprint = $this->fingerprint($sourceSystem, $exceptionClass, $normalizedMessage, $sourceId);
            $title = $this->buildTitle($sourceSystem, $normalizedMessage);

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

    private function fingerprint(string $sourceSystem, ?string $exceptionClass, string $message, ?string $sourceId = null): string
    {
        return md5($sourceSystem . '|' . ($exceptionClass ?? '') . '|' . $message . '|' . ($sourceId ?? ''));
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
}
