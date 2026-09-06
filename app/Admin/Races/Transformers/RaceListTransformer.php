<?php

namespace App\Admin\Races\Transformers;

use App\Flare\Models\GameRace;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class RaceListTransformer extends TransformerAbstract
{
    /**
     * Transform a Race into its Admin list-row representation.
     *
     * @param  GameRace  $gameRace  Race to transform.
     * @return array{id: int, name: string, description: string|null, image_url: string} Admin Race list-row representation.
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
     *
     * @param  GameRace  $gameRace  Race to resolve the image URL for.
     * @return string Public Race image URL.
     */
    private function resolveImageUrl(GameRace $gameRace): string
    {
        if (is_null($gameRace->image_path)) {
            return asset('character-images/knight-in-a-field.png');
        }

        return Storage::disk('public')->url($gameRace->image_path);
    }
}
