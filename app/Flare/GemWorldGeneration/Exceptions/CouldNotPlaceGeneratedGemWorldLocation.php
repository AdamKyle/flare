<?php

namespace App\Flare\GemWorldGeneration\Exceptions;

use RuntimeException;

class CouldNotPlaceGeneratedGemWorldLocation extends RuntimeException
{
    public static function withContext(
        string $type,
        int $attempts,
        int $mapId,
        string $mapPath,
        string $parentMapName,
        string $profileName,
        int $imageWidth,
        int $imageHeight,
        bool $imageLoaded,
        array $diagnostics = [],
    ): self {
        $imageStatus = $imageLoaded ? 'loaded' : 'failed to load';
        $message = "Could not place generated gem world location.\n"
            ."  Location type: {$type}\n"
            ."  Attempts: {$attempts}\n"
            ."  Map ID: {$mapId}\n"
            ."  Map path: {$mapPath}\n"
            ."  Parent map: {$parentMapName}\n"
            ."  Profile: {$profileName}\n"
            ."  Image dimensions: {$imageWidth}x{$imageHeight}\n"
            ."  Image status: {$imageStatus}";

        foreach ($diagnostics as $label => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $message .= "\n  {$label}: {$value}";
        }

        return new self($message);
    }
}
