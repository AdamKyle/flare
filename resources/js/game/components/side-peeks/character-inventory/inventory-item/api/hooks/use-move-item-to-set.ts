import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import UseMoveItemToSetDefinition from '../definitions/use-move-item-to-set-definition';
import UseMoveItemToSetRequestDefinition from '../definitions/use-move-item-to-set-request-definition';
import UseMoveItemToSetResponseDefinition from '../definitions/use-move-item-to-set-response-definition';

export const useMoveItemToSet = (): UseMoveItemToSetDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<
    UseMoveItemToSetDefinition['error'] | null
  >(null);
  const [requestParams, setRequestParams] = useState({
    character_id: 0,
    inventory_set_id: 0,
    inventory_slot_id: 0,
    on_success: (message: string) => {},
  });

  const moveItemToSet = useCallback(async () => {
    if (requestParams.character_id === 0) {
      return;
    }

    const url = getUrl(CharacterInventoryApiUrls.CHARACTER_MOVE_ITEM_TO_SET, {
      character: requestParams.character_id,
    });

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.post<
        UseMoveItemToSetResponseDefinition,
        AxiosRequestConfig<UseMoveItemToSetRequestDefinition>,
        UseMoveItemToSetRequestDefinition
      >(url, {
        set_id: requestParams.inventory_set_id,
        slot_id: requestParams.inventory_slot_id,
      });

      requestParams.on_success(result.message);
    } catch (err) {
      if (err instanceof AxiosError) {
        handleInactivity({
          setError: setError,
          response: err,
        });

        setError(err.response?.data || null);
      }
    } finally {
      setLoading(false);
    }
  }, [requestParams]);

  useEffect(() => {
    moveItemToSet().catch(() => {});
  }, [moveItemToSet]);

  return {
    loading,
    error,
    setRequestParams,
  };
};
