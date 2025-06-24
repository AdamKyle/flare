<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GemBagSlot;
use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\CharacterGemSlotsTransformer;
use App\Flare\Transformers\CharacterGemsTransformer;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Core\Traits\ResponseBuilder;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class CharacterGemBagService
{
    use ResponseBuilder;

    /**
     * @param Manager $manager
     * @param PlainDataSerializer $plainArraySerializer
     * @param CharacterGemSlotsTransformer $characterGemBagTransformer
     * @param CharacterGemsTransformer $gemsTransformer
     * @param Pagination $pagination
     */
    public function __construct(
        private readonly Manager $manager,
        private readonly PlainDataSerializer $plainArraySerializer,
        private readonly CharacterGemSlotsTransformer $characterGemBagTransformer,
        private readonly CharacterGemsTransformer $gemsTransformer,
        private readonly Pagination $pagination,
    ){}

    /**
     * Get gems from character bag.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @param array $filters
     * @return array
     */
    public function getGems(Character $character, int $perPage = 10, int $page = 1, string $searchText = '', array $filters = []): array
    {
        $gemSlots = $character->gemBag->gemSlots;

        if (!empty($searchText)) {
            $gemSlots = $gemSlots->filter(function (GemBagSlot $gemSlot) use ($searchText) {
                return stripos($gemSlot->gem->name, $searchText) !== false;
            });
        }

        if (isset($filters['tier'])) {
            $gemSlots = $gemSlots->filter(function (GemBagSlot $gemSlot) use ($filters) {
                return $gemSlot->gem->tier === $filters['tier'];
            });
        }

        $paginatedData = $this->pagination->buildPaginatedDate(
            $gemSlots,
            $this->characterGemBagTransformer,
            $perPage,
            $page
        );

        return $this->successResult($paginatedData);
    }

    public function getGemData(Character $character, GemBagSlot $gemSlot): array
    {

        if ($character->id !== $gemSlot->gemBag->character_id) {
            return $this->errorResult('No. Not yours!');
        }

        $gem = new Item($gemSlot->gem, $this->gemsTransformer);

        $this->manager->setSerializer($this->plainArraySerializer);

        $gem = $this->manager->createData($gem)->toArray();

        return $this->successResult(['gem' => $gem]);
    }
}
