<?php

namespace App\Admin\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImageHandlerService
{
    /**
     * Process the full content payload: for each content key that is an array of blocks,
     * delete images for removed blocks, upload/replace/remove images for kept/added blocks,
     * and return the normalized content array preserving incoming order.
     */
    public function process(Model $model, array $incomingContent, array $existingContent, bool $hasImage, string $disk, string $basePrefix): array
    {
        $existing = collect($existingContent);

        return collect($incomingContent)
            ->map(function ($value, $contentKey) use ($model, $existing, $hasImage, $disk, $basePrefix) {
                if (! $this->isBlockArray($value)) {
                    return $value;
                }

                $existingValue = $existing->get($contentKey, []);
                $existingBlocks = is_array($existingValue) ? $existingValue : [];

                return $this->processContentEntry(
                    $model,
                    $contentKey,
                    $value,
                    $existingBlocks,
                    $hasImage,
                    $disk,
                    $basePrefix
                );
            })
            ->toArray();
    }

    /**
     * Process one content entry (a block array) for a given key.
     */
    private function processContentEntry(Model $model, string $contentKey, array $incomingBlocks, array $existingBlocks, bool $hasImage, string $disk, string $basePrefix): array
    {
        $existingById = collect($existingBlocks)->keyBy('id');
        $incomingById = collect($incomingBlocks)->keyBy('id');

        $this->deleteRemovedBlockImages($existingById, $incomingById, $disk);

        return collect($incomingBlocks)
            ->values()
            ->map(function (array $block) use ($model, $contentKey, $existingById, $hasImage, $disk, $basePrefix) {
                $maybeId = Arr::get($block, 'id');
                $blockId = is_string($maybeId) && $maybeId !== '' ? $maybeId : Str::uuid()->toString();

                $existingBlock = $existingById->get($blockId);
                if (! is_array($existingBlock)) {
                    $existingBlock = [];
                }

                return $this->processSingleBlock(
                    $model,
                    $contentKey,
                    $blockId,
                    $block,
                    $existingBlock,
                    $hasImage,
                    $disk,
                    $basePrefix
                );
            })
            ->values()
            ->toArray();
    }

    /**
     * Delete images for any blocks that existed previously but are missing in the incoming payload.
     */
    private function deleteRemovedBlockImages(Collection $existingById, Collection $incomingById, string $disk): void
    {
        $existingIds = $existingById->keys();
        $incomingIds = $incomingById->keys();

        $existingIds->diff($incomingIds)->each(function ($id) use ($existingById, $disk) {
            $block = $existingById->get($id);
            if (! is_array($block)) {
                $block = [];
            }

            $path = $this->getExistingOriginalPath($block);

            if ($path !== null) {
                $this->deletePathIfExists($disk, $path);
            }
        });
    }

    /**
     * Resolve a single block's image state.
     */
    private function processSingleBlock(Model $model, string $contentKey, string $blockId, array $incomingBlock, array $existingBlock, bool $hasImage, string $disk, string $basePrefix): array
    {
        $existingPath = $this->getExistingOriginalPath($existingBlock);
        $hasKey = array_key_exists('image_url', $incomingBlock);
        $incomingValue = $hasKey ? $incomingBlock['image_url'] : null;

        if ($hasImage === false) {
            return $this->resolveNoImageMode($incomingBlock, $hasKey, $incomingValue, $existingPath, $disk);
        }

        if (! $hasKey) {
            $incomingBlock['original_path'] = $existingPath;
            $incomingBlock['image_url'] = $this->pathToUrl($disk, $existingPath);

            return $incomingBlock;
        }

        if ($incomingValue instanceof UploadedFile) {
            if ($existingPath !== null && $this->filesAreIdentical($disk, $existingPath, $incomingValue)) {
                $incomingBlock['original_path'] = $existingPath;
                $incomingBlock['image_url'] = $this->pathToUrl($disk, $existingPath);

                return $incomingBlock;
            }

            $newPath = $this->uploadFile($model, $contentKey, $blockId, $incomingValue, $disk, $basePrefix);

            if ($existingPath !== null) {
                $this->deletePathIfExists($disk, $existingPath);
            }

            $incomingBlock['original_path'] = $newPath;
            $incomingBlock['image_url'] = $this->pathToUrl($disk, $newPath);

            return $incomingBlock;
        }

        if (is_string($incomingValue)) {
            if ($this->isAbsoluteUrl($incomingValue)) {
                $incomingBlock['original_path'] = $existingPath;
                $incomingBlock['image_url'] = $incomingValue;

                return $incomingBlock;
            }

            $incomingBlock['original_path'] = $incomingValue;
            $incomingBlock['image_url'] = $this->pathToUrl($disk, $incomingValue);

            return $incomingBlock;
        }

        if ($incomingValue === null) {
            if ($existingPath !== null) {
                $this->deletePathIfExists($disk, $existingPath);
            }

            $incomingBlock['original_path'] = null;
            $incomingBlock['image_url'] = null;

            return $incomingBlock;
        }

        $incomingBlock['original_path'] = $existingPath;
        $incomingBlock['image_url'] = $this->pathToUrl($disk, $existingPath);

        return $incomingBlock;
    }

    /**
     * Apply "no-image" mode behavior.
     */
    private function resolveNoImageMode(array $block, bool $hasKey, mixed $incomingValue, ?string $existingPath, string $disk): array
    {
        if ($hasKey && $incomingValue === null && $existingPath !== null) {
            $this->deletePathIfExists($disk, $existingPath);
            $block['original_path'] = null;
            $block['image_url'] = null;

            return $block;
        }

        if ($hasKey && is_string($incomingValue)) {
            if ($this->isAbsoluteUrl($incomingValue)) {
                $block['original_path'] = $existingPath;
                $block['image_url'] = $incomingValue;

                return $block;
            }

            $block['original_path'] = $incomingValue;
            $block['image_url'] = $this->pathToUrl($disk, $incomingValue);

            return $block;
        }

        $block['original_path'] = $existingPath;
        $block['image_url'] = $this->pathToUrl($disk, $existingPath);

        return $block;
    }

    /**
     * Determine whether a value is a block array (array of arrays each having an 'id' key).
     */
    private function isBlockArray(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $collection = collect($value);
        if ($collection->isEmpty()) {
            return false;
        }

        return $collection->every(function ($item) {
            return is_array($item) && array_key_exists('id', $item);
        });
    }

    /**
     * Extract an existing original image path from a block if present and a string; otherwise null.
     */
    private function getExistingOriginalPath(array $block): ?string
    {
        $value = Arr::get($block, 'original_path');

        return is_string($value) ? $value : null;
    }

    /**
     * Delete a file at the given path on the given disk when it exists.
     */
    private function deletePathIfExists(string $disk, ?string $path): void
    {
        if ($path === null) {
            return;
        }

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    /**
     * Upload a file and return the stored relative path.
     */
    private function uploadFile(Model $model, string $contentKey, string $blockId, UploadedFile $file, string $disk, string $basePrefix): string
    {
        $nameSource = $model->getAttribute('name') ?? (string) $model->getKey();
        $nameSlug = Str::slug((string) $nameSource);

        $contentKeySlug = $this->sanitizeFilename($contentKey);
        $blockIdSlug = $this->sanitizeFilename($blockId);

        $prefix = $this->normalizeBasePrefix($basePrefix);
        $dir = $prefix.'/'.$nameSlug.'/'.$contentKeySlug.'/'.$blockIdSlug.'/images';

        $hash = md5_file($file->getRealPath()) ?: Str::random(40);
        $ext = $file->getClientOriginalExtension();
        $filename = $ext !== '' ? $hash.'.'.$ext : $hash;

        Storage::disk($disk)->putFileAs($dir, $file, $filename);

        return $dir.'/'.$filename;
    }

    /**
     * Compare an existing stored file with an uploaded file using md5 hashes; return true if identical.
     */
    private function filesAreIdentical(string $disk, ?string $existingPath, UploadedFile $file): bool
    {
        if ($existingPath === null) {
            return false;
        }

        try {
            $existingFull = Storage::disk($disk)->path($existingPath);
        } catch (Throwable) {
            return false;
        }

        if (! is_file($existingFull)) {
            return false;
        }

        $existingHash = md5_file($existingFull) ?: null;
        $incomingHash = md5_file($file->getRealPath()) ?: null;

        if ($existingHash === null || $incomingHash === null) {
            return false;
        }

        return hash_equals($existingHash, $incomingHash);
    }

    /**
     * Sanitize a filename by replacing spaces and removing disallowed characters.
     */
    private function sanitizeFilename(string $name): string
    {
        $name = str_replace(' ', '_', $name);

        return preg_replace('/[^A-Za-z0-9_\-\.]/', '', $name) ?? 'file';
    }

    /**
     * Convert a stored relative path into a full URL for the given disk.
     */
    private function pathToUrl(string $disk, ?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        if ($this->isAbsoluteUrl($path)) {
            return $path;
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Determine if the provided string is an absolute URL.
     */
    private function isAbsoluteUrl(string $value): bool
    {
        return Str::startsWith($value, ['http://', 'https://', '//']);
    }

    /**
     * Normalize a base prefix to a relative storage path segment.
     */
    private function normalizeBasePrefix(string $basePrefix): string
    {
        $prefix = trim($basePrefix, '/');

        if ($this->isAbsoluteUrl($prefix)) {
            $parts = parse_url($prefix);
            $path = $parts['path'] ?? '';
            $prefix = ltrim($path, '/');
        }

        if (Str::startsWith($prefix, 'storage/')) {
            $prefix = substr($prefix, 8);
        }

        return trim($prefix, '/');
    }
}
