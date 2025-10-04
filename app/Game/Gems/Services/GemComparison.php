<?php

namespace App\Game\Gems\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Gem;
use App\Flare\Models\Item as FlareItem;
use App\Flare\Transformers\CharacterGemsTransformer;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gems\Traits\GetItemAtonements;
use App\Game\Gems\Values\GemTypeValue;
use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class GemComparison
{
    use GetItemAtonements, ResponseBuilder;

    public function __construct(
        private readonly CharacterGemsTransformer $characterGemsTransformer,
        private readonly PlainDataSerializer $plainDataSerializer,
        private readonly Manager $manager
    ) {}

    public function compareGemForItem(Character $character, int $inventorySlotId, int $gemSlotId): array
    {
        $slot = $character->inventory->slots()->with('item')->find($inventorySlotId);

        if (is_null($slot)) {
            return $this->errorResult('Selected item was not found in your inventory.');
        }

        $gemSlot = $character->gemBag->gemSlots()->with('gem')->find($gemSlotId);

        if (is_null($gemSlot)) {
            return $this->errorResult('Selected gem was not found in your gem bag.');
        }

        $itemSocketData = [
            'item_sockets' => $slot->item->socket_count,
            'current_used_slots' => $slot->item->sockets->count(),
            'item_name' => $slot->item->affix_name,
        ];

        if ($slot->item->sockets->isEmpty()) {
            $gem = $gemSlot->gem->getAttributes();

            unset($gem['created_at']);
            unset($gem['updated_at']);

            return $this->successResult([
                'attached_gems' => [],
                'socket_data' => $itemSocketData,
                'has_gems_on_item' => false,
                'gem_to_attach' => $this->manager->setSerializer($this->plainDataSerializer)->createData(new Item($gemSlot->gem, $this->characterGemsTransformer))->toArray(),
                'when_replacing' => [],
                'if_replaced' => [],
            ]);
        }

        $comparisonData = [
            'when_replacing' => [],
            'if_replaced_atonements' => [],
        ];

        foreach ($slot->item->sockets as $socket) {
            if (! is_null($socket->gem)) {

                $gemComparison = $this->compareGems($gemSlot->gem, $socket->gem);

                if (! empty($gemComparison['when_replacing'])) {
                    $comparisonData['when_replacing'][] = $gemComparison['when_replacing'];
                }

                $comparisonData['if_replaced_atonements'][] = [
                    'name_to_replace' => $socket->gem->name,
                    'gem_id' => $socket->gem_id,
                    'data' => $this->ifReplaced($gemSlot->gem, $slot->item, $socket->gem->id),
                ];
            }
        }

        return $this->successResult([
            'attached_gems' => array_values($slot->item->sockets->map(function ($itemSocket) {
                $gem = new Item($itemSocket->gem, $this->characterGemsTransformer);

                return $this->manager->setSerializer($this->plainDataSerializer)->createData($gem)->toArray();
            })->toArray()),
            'socket_data' => $itemSocketData,
            'has_gems_on_item' => true,
            'gem_to_attach' => $this->manager->setSerializer($this->plainDataSerializer)->createData(new Item($gemSlot->gem, $this->characterGemsTransformer))->toArray(),
            'when_replacing' => $comparisonData['when_replacing'],
            'if_replacing_atonements' => $comparisonData['if_replaced_atonements'],
            'original_atonement' => $this->getElementAtonement($socket->item),
        ]);
    }

    public function ifItemGemsAreRemoved(FlareItem $item): array
    {
        $gems = $item->sockets->pluck('gem')->toArray();

        $atonementChanges = [
            'original_atonement' => $this->getElementAtonement($item),
            'atonement_changes' => [],
        ];

        foreach ($gems as $index => $gem) {
            $newListOfGems = $gems;

            array_splice($newListOfGems, $index, 1);

            $atonementChanges['atonement_changes'][] = [
                'gem_id_to_remove' => $gem['id'],
                'comparisons' => $this->getElementAtonementFromArray($newListOfGems),
            ];
        }

        return $atonementChanges;
    }

    /**
     * Compare two gems.
     *
     * @throws Exception
     */
    public function compareGems(Gem $gemToCompare, Gem $gemYouHave): array
    {

        $nonMatchingComparison = [];

        $atonements = [
            'primary_atonement',
            'secondary_atonement',
            'tertiary_atonement',
        ];

        foreach ($atonements as $atonement) {
            $data = $this->getComparisonForReplacing($gemToCompare, $gemYouHave, $atonement.'_type', $atonement.'_amount');

            if (! empty($data)) {
                $nonMatchingComparison = [...$nonMatchingComparison, ...$data];
            }
        }

        return [
            'when_replacing' => $nonMatchingComparison,
        ];
    }

    protected function ifReplaced(Gem $gemToCompare, FlareItem $item, int $gemToReplace): array
    {

        $gemToCompareAttributes = $gemToCompare->getAttributes();
        $itemsAttachedGems = $item->sockets->pluck('gem')->toArray();

        foreach ($itemsAttachedGems as $index => $attachedGem) {
            if ($attachedGem['id'] === $gemToReplace) {
                $itemsAttachedGems[$index] = $gemToCompareAttributes;
            }
        }

        return $this->getElementAtonementFromArray($itemsAttachedGems);
    }

    /**
     * Get Comparison data when the types on the gems do not match.
     *
     * @throws Exception
     */
    protected function getComparisonForReplacing(Gem $gemToCompare, Gem $gemYouHave, string $type, string $attribute): array
    {
        $comparisonOfAttribute = [];

        if ($gemToCompare->{$type} === $gemYouHave->{$type}) {
            $comparisonOfAttribute[$type] = (new GemTypeValue($gemToCompare->{$type}))->getNameOfAtonement();
            $comparisonOfAttribute[$attribute] = $gemToCompare->{$attribute} - $gemYouHave->{$attribute};
        }

        $comparisonOfAttribute['gem_you_have_id'] = $gemYouHave->id;
        $comparisonOfAttribute['tier'] = $gemToCompare->tier;
        $comparisonOfAttribute['name'] = $gemToCompare->name;

        return $comparisonOfAttribute;
    }
}
