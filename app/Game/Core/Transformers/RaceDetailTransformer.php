<?php

namespace App\Game\Core\Transformers;

use App\Flare\Models\GameRace;
use Illuminate\Support\Facades\Storage;

class RaceDetailTransformer
{
    /**
     * Transform a Race into its reusable factual detail representation.
     *
     * @param GameRace $gameRace
     * @return array
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
     * Resolve the Race's public image URL with the default portrait fallback.
     *
     * @param GameRace $gameRace
     * @return string
     */
    private function resolveImageUrl(GameRace $gameRace): string
    {
        if (is_null($gameRace->image_path)) {
            return asset('character-images/knight-in-a-field.png');
        }

        return Storage::disk('public')->url($gameRace->image_path);
    }
}
