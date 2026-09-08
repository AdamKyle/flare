import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import UseUseAlchemyItemApiDefinition from './definitions/use-use-alchemy-item-api-definition';
import UseUseAlchemyItemApiParams from './definitions/use-use-alchemy-item-api-params';
import UseAlchemyItemResponseDefinition from '../definitions/use-alchemy-item-response-definition';
import { UsableItemApiUrls } from '../enums/usable-item-api-urls';

interface UseAlchemyItemRequestDefinition {
  use_all: boolean;
}

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

  const useAlchemyItem = async (
    slotId: number,
    useAll: boolean
  ): Promise<void> => {
    if (slotId <= 0) {
      return;
    }

    setUsing(true);
    setError(null);
    setSuccessMessage(null);

    try {
      const response = await apiHandler.post<
        UseAlchemyItemResponseDefinition,
        never,
        UseAlchemyItemRequestDefinition
      >(
        getUrl(UsableItemApiUrls.USE_ALCHEMY_ITEM, {
          character: characterId,
          alchemyBagSlot: slotId,
        }),
        { use_all: useAll }
      );

      setSuccessMessage(response.message);
      onSuccess();
    } catch (requestError) {
      setError(errorMessage(requestError));
    } finally {
      setUsing(false);
    }
  };

  return { using, error, successMessage, useAlchemyItem };
};
