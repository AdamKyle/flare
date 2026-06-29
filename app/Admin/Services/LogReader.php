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

        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    public function readFrom(string $path, int $position): string|false
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        fseek($handle, $position);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    private function resolveLogPattern(string $pattern): string
    {
        if ($this->logRoot !== null) {
            $relative = preg_replace('#^logs/#', '', $pattern);

            return $this->logRoot . '/' . $relative;
        }

        return storage_path($pattern);
    }
}
