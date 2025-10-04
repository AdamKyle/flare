<?php

namespace App\Game\Survey\Services;

use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\SubmittedSurvey;
use App\Flare\Models\Survey;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Survey\Events\ShowSurvey;
use App\Game\Survey\Validator\SurveyValidator;

class SurveyService
{
    use ResponseBuilder;

    public function __construct(private readonly SurveyValidator $surveyValidator, private readonly RandomAffixGenerator $randomAffixGenerator) {}

    public function saveSurvey(Character $character, Survey $survey, array $params): array
    {

        $isValid = $this->surveyValidator->setSurveySections($survey)->validate($params);

        if (! $isValid) {
            return $this->errorResult('All fields, except editor fields, must be filled in. Please review your survey.');
        }

        SubmittedSurvey::create([
            'character_id' => $character->id,
            'survey_id' => $survey->id,
            'survey_response' => $params,
        ]);

        $character->user()->update([
            'is_showing_survey' => false,
        ]);

        $character = $character->refresh();

        $this->giveMythicalItem($character);

        $character = $character->refresh();

        event(new ShowSurvey($character->user, false));

        return $this->successResult([
            'message' => 'Survey submitted. Thank you and enjoy your new mythical item! You can find it in your character inventory.
            Click character sheet and either select inventory management for mobile or see inventory bottom right. This item has been
            rewarded regardless of your current inventory amount.',
        ]);
    }

    private function giveMythicalItem(Character $character)
    {
        $item = Item::whereNull('specialty_type')
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket', 'quest'])
            ->inRandomOrder()
            ->first();

        $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::MYTHIC);

        $newItem = $item->duplicate();

        $newItem->update([
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            'is_mythic' => true,
        ]);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $newItem->id,
        ]);

        event(new ServerMessageEvent($character->user, 'Thank you for submitting the survey, you were rewarded with a MYTHICAL item: '.$item->affix_name, $slot->id));
    }
}
