import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios from 'axios';
import { useEffect, useState } from 'react';

import BaseQuestItemDefinition from '../../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import InventoryQuestItemSlotResponseDefinition from '../definitions/inventory-quest-item-slot-response-definition';
import UseInventoryQuestItemSlotDefinition from '../definitions/use-inventory-quest-item-slot-definition';

/**
 * The inventory item endpoint returns a quest slot as the slot record with
 * the canonical quest item payload nested under `item`.
 */
export const useInventoryQuestItemSlot = (
  characterId: number,
  slotId: number
): UseInventoryQuestItemSlotDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [questItem, setQuestItem] = useState<BaseQuestItemDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (characterId <= 0 || slotId <= 0) {
      return;
    }

    const controller = new AbortController();

    const fetchQuestItem = async (): Promise<void> => {
      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.get<
          InventoryQuestItemSlotResponseDefinition,
          { slot_id: number }
        >(
          getUrl(CharacterInventoryApiUrls.CHARACTER_INVENTORY_ITEM, {
            character: characterId,
          }),
          { params: { slot_id: slotId }, signal: controller.signal }
        );

        setQuestItem(result.item);
        setLoading(false);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        setError(
          resolveApiErrorMessage(
            requestError,
            'Unable to load this quest item.'
          )
        );
        setLoading(false);
      }
    };

    void fetchQuestItem();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, characterId, slotId]);

  return { quest_item: questItem, loading, error };
};
