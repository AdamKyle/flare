<?php

namespace App\Admin\Services;

use App\Flare\Models\Item;
use InvalidArgumentException;

class GuideQuestService
{
    private const ALLOWED_BATCH_CRAFTING_TYPES = [
        'craft',
        'craft_and_enchant',
        'alchemy',
        'trinketry',
    ];

    public function cleanRequest(array $params): array
    {
        if (empty($params['required_batch_crafting_type']) || empty($params['required_batch_crafting_hours'])) {
            $params['required_batch_crafting_type'] = null;
            $params['required_batch_crafting_hours'] = null;
        } elseif (! in_array($params['required_batch_crafting_type'], self::ALLOWED_BATCH_CRAFTING_TYPES, true)) {
            throw new InvalidArgumentException('Invalid batch crafting guide quest requirement type.');
        }

        $params['required_batch_crafted_items'] = $this->cleanRequiredBatchCraftedItems($params['required_batch_crafted_items'] ?? null);

        if (! is_null($params['required_skill_level']) && is_null($params['required_skill'])) {
            $params['required_skill_level'] = null;
        }

        if (! is_null($params['required_passive_level']) && is_null($params['required_passive_skill'])) {
            $params['required_passive_level'] = null;
        }

        if (! is_null($params['required_faction_level']) && is_null($params['required_faction_id'])) {
            $params['required_faction_id'] = null;
        }

        return $params;
    }

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

            if (empty($itemId) || empty($amount) || (int) $amount < 1) {
                continue;
            }

            if (! in_array($source, ['inventory', 'alchemy_bag'], true)) {
                continue;
            }

            $item = Item::find($itemId);

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
                'item_id' => (int) $itemId,
                'amount' => (int) $amount,
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
