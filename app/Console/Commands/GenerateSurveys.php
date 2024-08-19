<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Flare\Models\Survey;
use App\Flare\Models\SubmittedSurvey;
use App\Flare\Models\Character;
use Illuminate\Support\Facades\Http;

class GenerateSurveys extends Command
{
    protected $signature = 'surveys:generate {count=1 : Number of surveys to generate} {--ignore-character-id= : Ignore character ID and use a specific ID}';
    protected $description = 'Generate random surveys with the structure of an existing survey';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $count = (int) $this->argument('count');
        $ignoreCharacterId = $this->option('ignore-character-id');

        $survey = Survey::first();
        if (!$survey) {
            $this->error('No surveys found in the database.');
            return 1;
        }

        $characterIds = Character::pluck('id')->toArray();
        if (empty($characterIds) && !$ignoreCharacterId) {
            $this->error('No characters found in the database.');
            return 1;
        }

        $sections = $survey->sections;

        for ($i = 0; $i < $count; $i++) {
            $surveyResponse = [];
            foreach ($sections as $section) {
                $sectionResponses = [];
                foreach ($section['input_types'] as $field) {
                    $label = $field['label'];
                    $type = $field['type'];
                    $options = $field['options'] ?? [];

                    if ($type === 'radio') {
                        $value = $this->getRandomValue($options);
                        $sectionResponses[$label] = ['type' => $type, 'value' => $value];
                    } elseif ($type === 'checkbox') {
                        $values = $this->getRandomValues($options);
                        $sectionResponses[$label] = ['type' => $type, 'value' => $values];
                    } elseif ($type === 'markdown') {
                        $sectionResponses[$label] = ['type' => $type, 'value' => $this->generateRandomMarkdown()];
                    }
                }
                $surveyResponse[] = $sectionResponses;
            }

            $characterId = $ignoreCharacterId !== null ? $ignoreCharacterId : $this->getRandomCharacterId($characterIds);

            SubmittedSurvey::create([
                'survey_id' => $survey->id,
                'character_id' => $characterId,
                'survey_response' => $surveyResponse,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info("{$count} surveys generated successfully.");
        return 0;
    }

    protected function getRandomValue(array $options)
    {
        return $options[array_rand($options)];
    }

    protected function getRandomValues(array $options)
    {
        $numOptions = rand(1, count($options));
        return array_rand(array_flip($options), $numOptions);
    }

    protected function generateRandomMarkdown()
    {
        $response = Http::get('https://brettterpstra.com/md-lipsum/api/1/long');

        if ($response->successful()) {
            return $response->body();
        }

        $errorMessage = 'Failed to generate markdown content.';
        $this->error($errorMessage);

        return $errorMessage;
    }

    protected function getRandomCharacterId(array $characterIds)
    {
        return $characterIds[array_rand($characterIds)];
    }
}

