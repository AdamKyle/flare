import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { isNil } from 'lodash';
import { useState, useCallback, useEffect } from 'react';
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
  const [error, setError] = useState<
    UseProcessItemActionDefinition['error'] | null
  >(null);
  const [loading, setLoading] = useState(false);

  const fetchUrlForAction = (): string | null => {
    if (isNil(requestData.action_type)) {
      return null;
    }

    return match(requestData.action_type)
      .with(ItemActions.SELL, () =>
        getUrl(CharacterInventoryApiUrls.CHARACTER_INVENTORY_SELL_ITEM, {
          character: requestData.character_id,
        })
      )
      .with(ItemActions.DESTROY, () =>
        getUrl(CharacterInventoryApiUrls.CHARACTER_INVENTORY_DESTROY_ITEM, {
          character: requestData.character_id,
        })
      )
      .with(ItemActions.DISENCHANT, () =>
        getUrl(CharacterInventoryApiUrls.CHARACTER_INVENTORY_DISENCHANT_ITEM, {
          character: requestData.character_id,
        })
      )
      .otherwise(() => null);
  };

  const processInventoryItemAction = useCallback(async () => {
    if (isNil(requestData.action_type) || !requestData.item_id) {
      return;
    }

    const url = fetchUrlForAction();

    if (!url) {
      return;
    }

    try {
      setLoading(true);

      const result = await apiHandler.post<
        UseProcessItemActionResponseDefinition,
        AxiosRequestConfig<UseProcessItemActionResponseDefinition>,
        UseProcessItemActionRequestDefinition
      >(url, {
        item_id: requestData.item_id,
      });

      requestData.on_success(result.message);
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [requestData]);

  useEffect(() => {
    processInventoryItemAction().catch(() => {});
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
