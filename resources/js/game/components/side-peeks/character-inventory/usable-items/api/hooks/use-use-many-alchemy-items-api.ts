import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import UseUseManyAlchemyItemsApiDefinition from './definitions/use-use-many-alchemy-items-api-definition';
import UseUseManyAlchemyItemsApiParams from './definitions/use-use-many-alchemy-items-api-params';
import UseManyAlchemyItemsResponseDefinition from '../definitions/use-many-alchemy-items-response-definition';
import { UsableItemApiUrls } from '../enums/usable-item-api-urls';

interface UseManyItemsRequestDefinition {
  items_to_use: number[];
}

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

  const useManyItems = async (itemsToUse: number[]): Promise<void> => {
    if (itemsToUse.length === 0) {
      return;
    }

    setUsing(true);
    setError(null);
    setSuccessMessage(null);

    try {
      const response = await apiHandler.post<
        UseManyAlchemyItemsResponseDefinition,
        never,
        UseManyItemsRequestDefinition
      >(getUrl(UsableItemApiUrls.USE_MANY_ITEMS, { character: characterId }), {
        items_to_use: itemsToUse,
      });

      setSuccessMessage(response.message);
      onSuccess();
    } catch (requestError) {
      setError(errorMessage(requestError));
    } finally {
      setUsing(false);
    }
  };

  return { using, error, successMessage, useManyItems };
};
