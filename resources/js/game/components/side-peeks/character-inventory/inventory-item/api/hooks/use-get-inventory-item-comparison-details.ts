import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { isNil } from 'lodash';
import { useCallback, useEffect, useState } from 'react';

import { ItemComparisonRow } from '../../../../../../api-definitions/items/item-comparison-details';
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

  const [data, setData] = useState<ItemComparisonRow[] | [] | null>(null);
  const [error, setError] =
    useState<UseGetInventoryItemComparisonDefinition['error']>(null);
  const [loading, setLoading] = useState<boolean>(true);

  const url = getUrl(CharacterInventoryApiUrls.CHARACTER_INVENTORY_COMPARISON, {
    character: character_id,
  });

  const fetchInventoryComparison = useCallback(async () => {
    if (slot_id === 0 || isNil(item_to_equip_type)) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        UseGetInventoryItemComparisonDetailsResponseDefinition,
        AxiosRequestConfig<
          AxiosResponse<UseGetInventoryItemComparisonDetailsResponseDefinition>
        >
      >(url, {
        params: {
          slot_id: slot_id,
          item_to_equip_type: item_to_equip_type,
        },
      });

      setData(result.details);
    } catch (err) {
      if (err instanceof AxiosError) {
        if (err.response?.status === 401) {
          setError({
            message:
              'You have been logged out due to inactivity. One moment while we redirect you.',
          });

          window.location.reload();

          return;
        }

        setError(err.response?.data || null);
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, url, slot_id, item_to_equip_type]);

  useEffect(() => {
    fetchInventoryComparison().catch(() => {});
  }, [fetchInventoryComparison]);

  return {
    data,
    error,
    loading,
  };
};
