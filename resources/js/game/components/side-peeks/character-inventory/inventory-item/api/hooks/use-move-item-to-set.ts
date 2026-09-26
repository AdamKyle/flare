import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import UseMoveItemToSetDefinition from '../definitions/use-move-item-to-set-definition';
import UseMoveItemToSetRequestDefinition from '../definitions/use-move-item-to-set-request-definition';
import UseMoveItemToSetRequestParams from '../definitions/use-move-item-to-set-request-params';
import UseMoveItemToSetResponseDefinition from '../definitions/use-move-item-to-set-response-definition';

export const useMoveItemToSet = (): UseMoveItemToSetDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<UseMoveItemToSetDefinition['error']>(null);
  const [requestParams, setRequestParams] =
    useState<UseMoveItemToSetRequestParams>({
      character_id: 0,
      inventory_set_id: 0,
      inventory_slot_id: 0,
      on_success: (_message: string) => {},
    });

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const moveItemToSet = useCallback(async (): Promise<void> => {
    if (requestParams.character_id === 0) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.post<
        UseMoveItemToSetResponseDefinition,
        AxiosRequestConfig<UseMoveItemToSetRequestDefinition>,
        UseMoveItemToSetRequestDefinition
      >(
        getUrl(CharacterInventoryApiUrls.CHARACTER_MOVE_ITEM_TO_SET, {
          character: requestParams.character_id,
        }),
        {
          set_id: requestParams.inventory_set_id,
          slot_id: requestParams.inventory_slot_id,
        },
        { signal: controller.signal }
      );

      if (abortControllerRef.current !== controller) {
        return;
      }

      requestParams.on_success(result.message);
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
          'Unable to move the item to the set.'
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
  }, [apiHandler, getUrl, handleInactivity, requestParams]);

  useEffect(() => {
    void moveItemToSet();
  }, [moveItemToSet]);

  return {
    loading,
    error,
    setRequestParams,
  };
};
