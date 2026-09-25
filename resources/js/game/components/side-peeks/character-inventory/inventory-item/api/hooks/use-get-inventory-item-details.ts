import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { EquippableItemDetailsDefinition } from '../../../../../../api-definitions/items/equippable-item-definitions/equippable-item-details-definition';
import UseGetInventoryItemDetailsApiDefinition from '../definitions/use-get-inventory-item-details-api-request-definition';
import UseGetInventoryItemDetailsResponse from '../definitions/use-get-inventory-item-details-response-definition';

export const useGetInventoryItemDetails = ({
  character_id,
  slot_id,
  url,
}: UseGetInventoryItemDetailsApiDefinition): UseGetInventoryItemDetailsResponse => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] =
    useState<UseGetInventoryItemDetailsResponse['data']>(null);
  const [error, setError] =
    useState<UseGetInventoryItemDetailsResponse['error']>(null);
  const [loading, setLoading] = useState<boolean>(true);

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const fetchInventoryItemDetails = useCallback(async (): Promise<void> => {
    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        EquippableItemDetailsDefinition,
        { slot_id: number }
      >(getUrl(url, { character: character_id, item: slot_id }), {
        params: { slot_id },
        signal: controller.signal,
      });

      if (abortControllerRef.current !== controller) {
        return;
      }

      setData(result);
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
          'Unable to load the item details.'
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
  }, [apiHandler, getUrl, handleInactivity, url, character_id, slot_id]);

  useEffect(() => {
    void fetchInventoryItemDetails();
  }, [fetchInventoryItemDetails]);

  return {
    data,
    error,
    loading,
    refetch: fetchInventoryItemDetails,
  };
};
