<?php

namespace App\Admin\Services;

class LogReader
{
    public function __construct(private readonly ?string $logRoot = null) {}

    public function withLogRoot(string $logRoot): static
    {
        return new self(rtrim($logRoot, '/'));
    }

    public function discoverFiles(array $patterns): array
    {
        $files = [];

        foreach ($patterns as $pattern) {
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

    public function fileSize(string $path): int|false
    {
        clearstatcache(true, $path);

        return filesize($path);
    }

    public function readTail(string $path, int $maxBytes): string|false
    {
        $fileSize = $this->fileSize($path);

        if ($fileSize === false || $fileSize === 0) {
            return '';
        }

        $offset = max(0, $fileSize - $maxBytes);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        if ($offset > 0) {
            fseek($handle, $offset);
            fgets($handle);
        }

        $content = stream_get_contents($handle, $maxBytes);
        fclose($handle);

        return $content;
    }

    public function readBackward(string $path, int $endPosition, int $maxBytes): array|false
    {
        $fileSize = $this->fileSize($path);

        if ($fileSize === false) {
            return false;
        }

        $endPosition = min(max(0, $endPosition), $fileSize);
        $startPosition = max(0, $endPosition - $maxBytes);
        $length = $endPosition - $startPosition;
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        fseek($handle, $startPosition);
        $content = $length > 0 ? fread($handle, $length) : '';
        fclose($handle);

        if ($content === false) {
            return false;
        }

        return [
            'content' => $content,
            'start' => $startPosition,
            'end' => $endPosition,
            'file_size' => $fileSize,
        ];
    }

    private function resolveLogPattern(string $pattern): string
    {
        if ($this->logRoot !== null) {
            $relative = preg_replace('#^logs/#', '', $pattern);

            return $this->logRoot.'/'.$relative;
        }

        return storage_path($pattern);
    }
}
