<?php

namespace App\Admin\Services;

use App\Flare\Models\GuideQuest;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class GuideQuestService
{
    private const ALLOWED_BATCH_CRAFTING_TYPES = [
        'craft',
        'craft_and_enchant',
        'alchemy',
        'trinketry',
    ];

    /**
     * @param ImageHandlerService $imageHandlerService
     */
    public function __construct(private readonly ImageHandlerService $imageHandlerService) {}

    /**
     * Create or update a Guide Quest from validated Admin form data.
     *
     * @param array $payload
     * @param GuideQuest $guideQuest
     * @return GuideQuest
     */
    public function upsert(array $payload, GuideQuest $guideQuest): GuideQuest
    {
        $guideQuestId = $payload['guide_quest_id'];
        $hasImage = $payload['has_image'] === '1';
        $incomingContent = $this->cleanRequest($payload['content']);
        $model = $this->resolveModel($guideQuestId, $guideQuest);
        $existingContent = collect($incomingContent)
            ->mapWithKeys(function ($unusedValue, $key) use ($model) {
                return [$key => $model->getAttribute($key)];
            })
            ->toArray();
        $finalContent = $this->imageHandlerService->process(
            $model,
            $incomingContent,
            $existingContent,
            $hasImage,
            'guide-quest-images',
            'guide-quests',
        );

        $model->fill($finalContent);
        $model->save();

        return $model->fresh();
    }

    /**
     * Normalize dependent Guide Quest requirement values.
     *
     * @param array $params
     * @return array
     */
    public function cleanRequest(array $params): array
    {
        if (empty($params['required_batch_crafting_type']) || empty($params['required_batch_crafting_hours'])) {
            $params['required_batch_crafting_type'] = null;
            $params['required_batch_crafting_hours'] = null;
        }

        if (! is_null($params['required_batch_crafting_type']) && ! in_array($params['required_batch_crafting_type'], self::ALLOWED_BATCH_CRAFTING_TYPES, true)) {
            throw new InvalidArgumentException('Invalid batch crafting guide quest requirement type.');
        }

        $params['required_batch_crafted_items'] = $this->cleanRequiredBatchCraftedItems($params['required_batch_crafted_items'] ?? null);

        if (! is_null($params['required_skill_level'] ?? null) && is_null($params['required_skill'] ?? null)) {
            $params['required_skill_level'] = null;
        }

        if (! is_null($params['required_passive_level'] ?? null) && is_null($params['required_passive_skill'] ?? null)) {
            $params['required_passive_level'] = null;
        }

        if (! is_null($params['required_faction_level'] ?? null) && is_null($params['required_faction_id'] ?? null)) {
            $params['required_faction_id'] = null;
        }

        return $params;
    }

    /**
     * Resolve the existing Guide Quest model when one is being edited.
     *
     * @param string $id
     * @param Model $model
     * @return Model
     */
    private function resolveModel(string $id, Model $model): Model
    {
        $foundInstance = $model::find($id);

        if (is_null($foundInstance)) {
            return $model;
        }

        return $foundInstance;
    }

    /**
     * Normalize valid Batch Crafted Item requirement rows.
     *
     * @param array|null $batchCraftedItems
     * @return array|null
     */
    private function cleanRequiredBatchCraftedItems(?array $batchCraftedItems): ?array
    {
        if (empty($batchCraftedItems)) {
            return null;
        }

        $cleanedRows = [];

        foreach ($batchCraftedItems as $batchCraftedItem) {
            if (! is_array($batchCraftedItem)) {
                continue;
            }

            $itemId = $batchCraftedItem['item_id'] ?? null;
            $amount = $batchCraftedItem['amount'] ?? null;
            $source = $batchCraftedItem['source'] ?? 'inventory';

            $normalizedItemId = filter_var($itemId, FILTER_VALIDATE_INT);
            $normalizedAmount = filter_var($amount, FILTER_VALIDATE_INT);

            if ($normalizedItemId === false || $normalizedAmount === false || $normalizedAmount < 1) {
                continue;
            }

            if (! in_array($source, ['inventory', 'alchemy_bag'], true)) {
                continue;
            }

            $item = Item::find($normalizedItemId);

            if (is_null($item)) {
                continue;
            }

            if ($source === 'alchemy_bag' && $item->type !== 'alchemy') {
                continue;
            }

            if ($source === 'inventory' && $item->type === 'alchemy') {
                continue;
            }

            $cleanedRows[] = [
                'source' => $source,
                'item_id' => $normalizedItemId,
                'amount' => $normalizedAmount,
                'must_be_enchanted' => $source === 'alchemy_bag'
                    ? false
                    : filter_var($batchCraftedItem['must_be_enchanted'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            if (count($cleanedRows) === 2) {
                break;
            }
        }

        if (empty($cleanedRows)) {
            return null;
        }

        return array_values($cleanedRows);
    }
}
