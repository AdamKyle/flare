import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import UseEquipItemApiDefinition from '../definitions/use-equip-item-api-definition';
import UseEquipItemApiResponseDefinition from '../definitions/use-equip-item-api-response-definition';
import UseEquipItemParamsDefinition from '../definitions/use-equip-item-params-definition';
import UseGetInventoryItemComparisonDefinition from '../definitions/use-equip-item-request-params-definition';
import UseEquipItemRequestParamsDefinition from '../definitions/use-equip-item-request-params-definition';

export const useEquipItem = ({
  character_id,
  on_success,
}: UseEquipItemParamsDefinition): UseEquipItemApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [error, setError] = useState<UseEquipItemApiDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [requestParams, setRequestParams] =
    useState<UseGetInventoryItemComparisonDefinition>({
      position: null,
      slot_id: 0,
      equip_type: null,
    });

  const url = getUrl(CharacterInventoryApiUrls.CHARACTER_EQUIP_ITEM, {
    character: character_id,
  });

  const equipSelectedItem = useCallback(
    async () => {
      if (requestParams.slot_id === 0) {
        return null;
      }

      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.post<
          UseEquipItemApiResponseDefinition,
          AxiosRequestConfig<UseEquipItemRequestParamsDefinition>,
          UseEquipItemRequestParamsDefinition
        >(url, {
          slot_id: requestParams.slot_id,
          position: requestParams.position,
          equip_type: requestParams.equip_type,
        });

        on_success(result.message);

        setLoading(false);
      } catch (err) {
        if (err instanceof AxiosError) {
          setError(err.response?.data || null);
        }
      } finally {
        setLoading(false);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [apiHandler, url, requestParams]
  );

  useEffect(() => {
    if (character_id <= 0) {
      return;
    }

    equipSelectedItem().catch(() => {});
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [equipSelectedItem, requestParams]);

  return {
    error,
    loading,
    setRequestParams,
  };
};
