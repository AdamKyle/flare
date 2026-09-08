<?php

namespace App\Admin\Races\Services;

use App\Admin\Races\Requests\RaceIndexRequest;
use App\Flare\Models\GameRace;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class RaceService
{
    /**
     * Paginate the Races list for the validated Admin index request.
     */
    public function paginate(RaceIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $sortKey = $request->validated('sort_key');
        $sortDirection = $request->validated('sort_direction');

        $query = GameRace::query();

        if (! empty($searchText)) {
            $query->where(function ($searchQuery) use ($searchText) {
                $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchText.'%');
            });
        }

        $query->orderBy($sortKey, $sortDirection)
            ->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Create a new Race from the validated form data and optional uploaded image.
     */
    public function create(array $validatedData, ?UploadedFile $image): GameRace
    {
        $imagePath = is_null($image) ? null : $this->storeImage($image);

        return GameRace::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'image_path' => $imagePath,
        ]);
    }

    /**
     * Update a Race and replace its image when a new image is supplied.
     */
    public function update(GameRace $gameRace, array $validatedData, ?UploadedFile $image): GameRace
    {
        $previousImagePath = $gameRace->image_path;
        $imagePath = is_null($image) ? $previousImagePath : $this->storeImage($image);

        $gameRace->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'image_path' => $imagePath,
        ]);

        if (! is_null($image) && ! is_null($previousImagePath)) {
            Storage::disk('public')->delete($previousImagePath);
        }

        return $gameRace->refresh();
    }

    /**
     * Store an uploaded Race image under the `race-images/` public-disk directory.
     */
    private function storeImage(UploadedFile $image): string
    {
        return $image->store('race-images', 'public');
    }
}
