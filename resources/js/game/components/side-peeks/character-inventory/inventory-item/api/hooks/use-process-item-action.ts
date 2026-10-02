import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import { isNil } from 'lodash';
import { useCallback, useEffect, useRef, useState } from 'react';
import { match } from 'ts-pattern';

import { ItemActions } from '../../../../../../reusable-components/item/enums/item-actions';
import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import UseProcessItemActionDefinition from '../definitions/use-process-item-action-definition';
import UseProcessItemActionRequestDefinition from '../definitions/use-process-item-action-request-definition';
import UseProcessItemActionRequestParams from '../definitions/use-process-item-action-request-params-definition';
import UseProcessItemActionResponseDefinition from '../definitions/use-process-item-action-response-definition';

export const useProcessItemAction = (): UseProcessItemActionDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [requestData, setRequestData] =
    useState<UseProcessItemActionRequestParams>({
      action_type: null,
      item_id: null,
      character_id: 0,
      on_success: () => {},
    });
  const [error, setError] =
    useState<UseProcessItemActionDefinition['error']>(null);
  const [loading, setLoading] = useState(false);

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const processInventoryItemAction = useCallback(async (): Promise<void> => {
    const { action_type, item_id, character_id, on_success } = requestData;

    if (isNil(action_type) || !item_id) {
      return;
    }

    const url = match(action_type)
      .with(ItemActions.SELL, () =>
        getUrl(CharacterInventoryApiUrls.CHARACTER_INVENTORY_SELL_ITEM, {
          character: character_id,
        })
      )
      .with(ItemActions.DESTROY, () =>
        getUrl(CharacterInventoryApiUrls.CHARACTER_INVENTORY_DESTROY_ITEM, {
          character: character_id,
        })
      )
      .with(ItemActions.DISENCHANT, () =>
        getUrl(CharacterInventoryApiUrls.CHARACTER_INVENTORY_DISENCHANT_ITEM, {
          character: character_id,
        })
      )
      .otherwise(() => null);

    if (!url) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);

    try {
      const result = await apiHandler.post<
        UseProcessItemActionResponseDefinition,
        AxiosRequestConfig<UseProcessItemActionRequestDefinition>,
        UseProcessItemActionRequestDefinition
      >(url, { item_id }, { signal: controller.signal });

      if (abortControllerRef.current !== controller) {
        return;
      }

      on_success(result.message);
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
          'Unable to complete the item action.'
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
  }, [apiHandler, getUrl, handleInactivity, requestData]);

  useEffect(() => {
    void processInventoryItemAction();
  }, [processInventoryItemAction]);

  const resetError = () => {
    setError(null);
  };

  return {
    setRequestData,
    resetError,
    error,
    loading,
  };
};
