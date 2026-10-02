import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseUseManyAlchemyItemsApiDefinition from './definitions/use-use-many-alchemy-items-api-definition';
import UseUseManyAlchemyItemsApiParams from './definitions/use-use-many-alchemy-items-api-params';
import UseManyAlchemyItemsRequestDefinition from '../definitions/use-many-alchemy-items-request-definition';
import UseManyAlchemyItemsResponseDefinition from '../definitions/use-many-alchemy-items-response-definition';
import { UsableItemApiUrls } from '../enums/usable-item-api-urls';

const errorMessage = (error: unknown): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ?? 'Unable to use the selected items.')
    : 'Unable to use the selected items.';

export const useUseManyAlchemyItemsApi = ({
  characterId,
  onSuccess,
}: UseUseManyAlchemyItemsApiParams): UseUseManyAlchemyItemsApiDefinition => {
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

  const useManyItems = useCallback(
    async (itemsToUse: number[]): Promise<void> => {
      if (itemsToUse.length === 0) {
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
          UseManyAlchemyItemsResponseDefinition,
          AxiosRequestConfig<UseManyAlchemyItemsRequestDefinition>,
          UseManyAlchemyItemsRequestDefinition
        >(
          getUrl(UsableItemApiUrls.USE_MANY_ITEMS, { character: characterId }),
          { items_to_use: itemsToUse },
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

  return { using, error, successMessage, useManyItems };
};
