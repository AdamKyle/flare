import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import UseUseCurrencyCacheApiDefinition from './definitions/use-use-currency-cache-api-definition';
import UseUseCurrencyCacheApiParams from './definitions/use-use-currency-cache-api-params';
import UseCurrencyCacheResponseDefinition from '../definitions/use-currency-cache-response-definition';
import { UsableItemApiUrls } from '../enums/usable-item-api-urls';

const errorMessage = (error: unknown): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ??
      'Unable to use this Compensation Cache.')
    : 'Unable to use this Compensation Cache.';

export const useUseCurrencyCacheApi = ({
  characterId,
  onSuccess,
}: UseUseCurrencyCacheApiParams): UseUseCurrencyCacheApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [usingSlotId, setUsingSlotId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const useCurrencyCache = async (slotId: number): Promise<void> => {
    if (slotId <= 0 || usingSlotId !== null) {
      return;
    }

    setUsingSlotId(slotId);
    setError(null);
    setSuccessMessage(null);

    try {
      const response = await apiHandler.post<
        UseCurrencyCacheResponseDefinition,
        never,
        Record<string, never>
      >(
        getUrl(UsableItemApiUrls.USE_CURRENCY_CACHE, {
          character: characterId,
          alchemyBagSlot: slotId,
        }),
        {}
      );

      setSuccessMessage(response.message);
      onSuccess();
    } catch (requestError) {
      setError(errorMessage(requestError));
    } finally {
      setUsingSlotId(null);
    }
  };

  return { usingSlotId, error, successMessage, useCurrencyCache };
};
