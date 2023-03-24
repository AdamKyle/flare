<?php

namespace App\Game\Core\Gems\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Gem;
use App\Flare\Transformers\CharacterGemsTransformer;
use App\Game\Core\Gems\Values\GemTypeValue;
use App\Game\Core\Traits\ResponseBuilder;
use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class GemComparison {

    use ResponseBuilder;

    private Manager $manager;

    private CharacterGemsTransformer $characterGemsTransformer;

    public function __construct(CharacterGemsTransformer $characterGemsTransformer, Manager $manager) {
        $this->characterGemsTransformer = $characterGemsTransformer;
        $this->manager                  = $manager;
    }

    public function compareGemForItem(Character $character, int $inventorySlotId, int $gemSlotId): array {
        $slot = $character->inventory->slots()->with('item')->find($inventorySlotId);

        if (is_null($slot)) {
            return $this->errorResult('Selected item was not found in your inventory.');
        }

        $gemSlot = $character->gemBag->gemSlots()->with('gem')->find($gemSlotId);

        if (is_null($gemSlot)) {
            return $this->errorResult('Selected gem was not found in your gem bag.');
        }

        if ($slot->item->sockets->isEmpty()) {
            $gem = $gemSlot->gem->getAttributes();

            unset($gem['created_at']);
            unset($gem['updated_at']);

            return $this->successResult([
                'attached_gems'      => [],
                'has_gems_on_item'   => false,
                'gem_to_attach'      => $this->manager->createData(new Item($gemSlot->gem, $this->characterGemsTransformer))->toArray(),
                'when_replacing'     => [],
            ]);
        }

        $comparisonData = [
            'when_replacing' => [],
        ];

        foreach ($slot->item->sockets as $socket) {
            if (!is_null($socket->gem)) {

                $gemComparison = $this->compareGems($gemSlot->gem, $socket->gem);

                if (!empty($gemComparison['when_replacing'])) {
                    $comparisonData['when_replacing'][] = $gemComparison['when_replacing'];
                }
            }
        }

        return $this->successResult([
            'attached_gems'      => array_values($slot->item->sockets->map(function($itemSocket) {
                $gem = new Item($itemSocket->gem, $this->characterGemsTransformer);

                return $this->manager->createData($gem)->toArray();
            })->toArray()),
            'has_gems_on_item'   => true,
            'gem_to_attach'      => $this->manager->createData(new Item($gemSlot->gem, $this->characterGemsTransformer))->toArray(),
            'when_replacing'     => $comparisonData['when_replacing'],
        ]);
    }

    /**
     * Compare two gems.
     *
     * @param Gem $gemToCompare
     * @param Gem $gemYouHave
     * @return array
     * @throws Exception
     */
    public function compareGems(Gem $gemToCompare, Gem $gemYouHave): array {

        $nonMatchingComparison = [];

        $atonements = [
            'primary_atonement',
            'secondary_atonement',
            'tertiary_atonement',
        ];

        foreach ($atonements as $atonement) {
            $data = $this->getComparisonForReplacing($gemToCompare, $gemYouHave, $atonement . '_type', $atonement . '_amount');

            if (!empty($data)) {
                $nonMatchingComparison = [...$nonMatchingComparison, ...$data];
            }
        }

        return [
            'when_replacing' => $nonMatchingComparison,
        ];
    }

    /**
     * Get Comparison data when the types on the gems do not match.
     *
     * @param Gem $gemToCompare
     * @param Gem $gemYouHave
     * @param string $type
     * @param string $attribute
     * @return array
     * @throws Exception
     */
    protected function getComparisonForReplacing(Gem $gemToCompare, Gem $gemYouHave, string $type, string $attribute): array {
        $comparisonOfAttribute = [];

        if ($gemToCompare->{$type} === $gemYouHave->{$type}) {
            $comparisonOfAttribute[$type]               = (new GemTypeValue($gemToCompare->{$type}))->getNameOfAtonement();
            $comparisonOfAttribute[$attribute]          = $gemToCompare->{$attribute} - $gemYouHave->{$attribute};
        } else {
            $comparisonOfAttribute[$type]               = (new GemTypeValue($gemToCompare->{$type}))->getNameOfAtonement();
            $comparisonOfAttribute[$attribute]          = $gemToCompare->{$attribute};
        }

        $comparisonOfAttribute['gem_you_have_id'] = $gemYouHave->id;
        $comparisonOfAttribute['tier']            = $gemToCompare->tier;
        $comparisonOfAttribute['name']            = $gemToCompare->name;

        return $comparisonOfAttribute;
    }
}
