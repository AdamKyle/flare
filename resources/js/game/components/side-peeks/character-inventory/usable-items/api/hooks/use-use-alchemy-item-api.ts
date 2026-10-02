import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseUseAlchemyItemApiDefinition from './definitions/use-use-alchemy-item-api-definition';
import UseUseAlchemyItemApiParams from './definitions/use-use-alchemy-item-api-params';
import UseAlchemyItemRequestDefinition from '../definitions/use-alchemy-item-request-definition';
import UseAlchemyItemResponseDefinition from '../definitions/use-alchemy-item-response-definition';
import { UsableItemApiUrls } from '../enums/usable-item-api-urls';

const errorMessage = (error: unknown): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ?? 'Unable to use the item.')
    : 'Unable to use the item.';

export const useUseAlchemyItemApi = ({
  characterId,
  onSuccess,
}: UseUseAlchemyItemApiParams): UseUseAlchemyItemApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [using, setUsing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const useAlchemyItem = useCallback(
    async (slotId: number, useAll: boolean): Promise<void> => {
      if (slotId <= 0) {
        return;
      }

      abortControllerRef.current?.abort();
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setUsing(true);
      setError(null);
      setSuccessMessage(null);

      try {
        const response = await apiHandler.post<
          UseAlchemyItemResponseDefinition,
          AxiosRequestConfig<UseAlchemyItemRequestDefinition>,
          UseAlchemyItemRequestDefinition
        >(
          getUrl(UsableItemApiUrls.USE_ALCHEMY_ITEM, {
            character: characterId,
            alchemyBagSlot: slotId,
          }),
          { use_all: useAll },
          { signal: controller.signal }
        );

        if (abortControllerRef.current !== controller) {
          return;
        }

        setSuccessMessage(response.message);
        onSuccess();
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        if (abortControllerRef.current !== controller) {
          return;
        }

        setError(errorMessage(requestError));
      } finally {
        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setUsing(false);
        }
      }
    },
    [apiHandler, getUrl, characterId, onSuccess]
  );

  return { using, error, successMessage, useAlchemyItem };
};
