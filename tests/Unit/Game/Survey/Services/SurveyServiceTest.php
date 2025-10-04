<?php

namespace Tests\Unit\Game\Survey\Services;

use App\Game\Survey\Services\SurveyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateSurvey;

class SurveyServiceTest extends TestCase
{
    use CreateItem, CreateSurvey, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?SurveyService $surveyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
        $this->surveyService = resolve(SurveyService::class);

    }

    public function test_survey_is_missing_fields()
    {
        $character = $this->character->getCharacter();
        $survey = $this->createSurvey();

        $result = $this->surveyService->saveSurvey($character, $survey, []);

        $this->assertEquals($result['status'], 422);
        $this->assertEquals($result['message'], 'All fields, except editor fields, must be filled in. Please review your survey.');

    }

    public function test_survey_is_submitted()
    {
        $character = $this->character->getCharacter();
        $survey = $this->createSurvey();

        $this->createItem();

        $result = $this->surveyService->saveSurvey($character, $survey, [
            [
                'Some Radio Label' => ['value' => 'Option 1', 'type' => 'radio'],
                'Some Checkbox Label' => ['value' => ['Option 1'], 'type' => 'checkbox'],
                'Some markdown Label' => ['value' => 'Some content', 'type' => 'markdown'],
            ],
        ]);

        $this->assertEquals($result['status'], 200);
        $this->assertEquals($result['message'], 'Survey submitted. Thank you and enjoy your new mythical item! You can find it in your character inventory.
            Click character sheet and either select inventory management for mobile or see inventory bottom right. This item has been
            rewarded regardless of your current inventory amount.');

        $character = $character->refresh();

        $inventorySlot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_mythic;
        })->first();

        $this->assertNotNull($inventorySlot);
    }
}
