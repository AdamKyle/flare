<?php

namespace Tests\Feature\Game\Survey\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateSurvey;

class SurveyControllerTest extends TestCase
{
    use CreateItem, CreateSurvey, RefreshDatabase;

    private ?Character $character = null;

    private ?Survey $survey = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->survey = $this->createSurvey();

        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->survey = null;
    }

    public function test_fetch_survey()
    {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/survey/'.$this->survey->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['title'], $this->survey->title);
        $this->assertEquals($jsonData['description'], $this->survey->description);
        $this->assertEquals($jsonData['sections'], $this->survey->sections);
    }

    public function test_submit_survey_response()
    {

        $this->createItem();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/survey/submit/'.$this->survey->id.'/'.$this->character->id, [
                [
                    'Some Radio Label' => ['value' => 'Option 1', 'type' => 'radio'],
                    'Some Checkbox Label' => ['value' => ['Option 1'], 'type' => 'checkbox'],
                    'Some markdown Label' => ['value' => 'Some content', 'type' => 'markdown'],
                ],
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['message'], 'Survey submitted. Thank you and enjoy your new mythical item! You can find it in your character inventory.
            Click character sheet and either select inventory management for mobile or see inventory bottom right. This item has been
            rewarded regardless of your current inventory amount.');

        $character = $this->character->refresh();

        $inventorySlot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_mythic;
        })->first();

        $this->assertNotNull($inventorySlot);
    }
}
