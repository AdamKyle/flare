<?php

namespace App\Admin\Races\Transformers;

use App\Flare\Models\GameRace;
use Illuminate\Support\Facades\Storage;

class RaceFormTransformer
{
    /**
     * Transform a Race into its Admin save-response / form-value representation.
     */
    public function transform(GameRace $gameRace): array
    {
        return [
            'id' => $gameRace->id,
            'name' => $gameRace->name,
            'description' => $gameRace->description,
            'image_url' => $this->resolveImageUrl($gameRace),
        ];
    }

    /**
     * Resolve the Race's public image URL, falling back to the knight placeholder image.
     */
    private function resolveImageUrl(GameRace $gameRace): string
    {
        if (is_null($gameRace->image_path)) {
            return asset('character-images/knight-in-a-field.png');
        }

        return Storage::disk('public')->url($gameRace->image_path);
    }
}
