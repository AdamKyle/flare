<?php

namespace App\Admin\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Flare\Models\Character;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Core\Values\FeedbackType;

class SuggestionAndBugsService {

    use ResponseBuilder;

    public function createEntry(Character $character, array $params): array {

        $paths = [];

        if (isset($params['files'])) {
            $paths = $this->storeImages($params['files'], Str::snake($params['title']));
        }

        SuggestionAndBugs::create([
            'character_id' => $character->id,
            'title' => $params['title'],
            'type' => $params['type'],
            'platform' => $params['platform'],
            'description' => $params['description'],
            'uploaded_image_paths' => $paths,
        ]);

        $feedBackType = $params['type'] === FeedbackType::BUG ? 'Bug Report' : 'Suggestion';

        return $this->successResult(['message' => 'Thank you for submitting your: ' . $feedBackType . '. The Creator will take a look shortly! Please do not post the same bug or suggestion. If you have further questions, please reach out in chat or Discord, which you can access by clicking/tapping the top profile icon and selecting discord.']);
    }
    private function storeImages(array $files, string $pathName): array {
        $paths = [];

        foreach ($files as $file) {
            $paths[] = Storage::disk('suggestions-and-bugs')->putFile($pathName, $file);
        }

        return $paths;
    }
}
