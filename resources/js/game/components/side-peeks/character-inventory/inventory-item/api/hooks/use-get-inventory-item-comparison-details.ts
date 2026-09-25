import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError } from 'axios';
import { isNil } from 'lodash';
import { useCallback, useEffect, useRef, useState } from 'react';

import { InventoryItemTypes } from '../../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import UseGetInventoryItemComparisonDefinition from '../definitions/use-get-inventory-item-comparison-definition';
import UseGetInventoryItemComparisonDetailsParams from '../definitions/use-get-inventory-item-comparison-details-params-definition';
import UseGetInventoryItemComparisonDetailsResponseDefinition from '../definitions/use-get-inventory-item-comparison-details-response-definition';

export const useGetInventoryItemComparisonDetails = ({
  character_id,
  item_to_equip_type,
  slot_id,
}: UseGetInventoryItemComparisonDetailsParams): UseGetInventoryItemComparisonDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] =
    useState<UseGetInventoryItemComparisonDefinition['data']>(null);
  const [error, setError] =
    useState<UseGetInventoryItemComparisonDefinition['error']>(null);
  const [loading, setLoading] = useState<boolean>(true);

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const fetchInventoryComparison = useCallback(async (): Promise<void> => {
    if (slot_id === 0 || isNil(item_to_equip_type)) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        UseGetInventoryItemComparisonDetailsResponseDefinition,
        { slot_id: number; item_to_equip_type: InventoryItemTypes }
      >(
        getUrl(CharacterInventoryApiUrls.CHARACTER_INVENTORY_COMPARISON, {
          character: character_id,
        }),
        {
          params: { slot_id, item_to_equip_type },
          signal: controller.signal,
        }
      );

      if (abortControllerRef.current !== controller) {
        return;
      }

      setData({
        item_to_equip: result.itemToEquip,
        details: result.details,
      });
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (abortControllerRef.current !== controller) {
        return;
      }

      setError({
        message: resolveApiErrorMessage(
          requestError,
          'Unable to load the item comparison.'
        ),
      });

      if (requestError instanceof AxiosError) {
        handleInactivity({ response: requestError, setError });
      }
    } finally {
      if (abortControllerRef.current === controller) {
        abortControllerRef.current = null;
        setLoading(false);
      }
    }
  }, [
    apiHandler,
    getUrl,
    handleInactivity,
    character_id,
    slot_id,
    item_to_equip_type,
  ]);

  useEffect(() => {
    void fetchInventoryComparison();
  }, [fetchInventoryComparison]);

  return {
    data,
    error,
    loading,
  };
};
