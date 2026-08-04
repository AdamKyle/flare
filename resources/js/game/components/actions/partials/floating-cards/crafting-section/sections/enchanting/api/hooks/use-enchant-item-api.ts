import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import { buildEnchantItemRequest } from '../../utils/build-enchant-item-request';
import EnchantItemRequestDefinition from '../definitions/enchant-item-request-definition';
import EnchantingApiResponseDefinition from '../definitions/enchanting-api-response-definition';
import { EnchantingApiUrls } from '../enums/enchanting-api-urls';
import UseEnchantItemApiDefinition from './definitions/use-enchant-item-api-definition';
import UseEnchantItemApiParams from './definitions/use-enchant-item-api-params';

const getErrorMessage = (error: unknown): string => {
  if (error instanceof AxiosError) {
    return error.response?.data?.message ?? 'Unable to enchant the item.';
  }

  return 'Unable to enchant the item.';
};

export const useEnchantItemApi = ({
  characterId,
  slotId,
  affixIds,
  source,
}: UseEnchantItemApiParams): UseEnchantItemApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [submitting, setSubmitting] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  const enchant = async (): Promise<EnchantingApiResponseDefinition | null> => {
    const request = buildEnchantItemRequest(slotId, affixIds, source);

    if (!request) {
      return null;
    }

    setSubmitting(true);
    setError(null);

    try {
      return await apiHandler.post<
        EnchantingApiResponseDefinition,
        never,
        EnchantItemRequestDefinition
      >(getUrl(EnchantingApiUrls.ENCHANT, { character: characterId }), request);
    } catch (requestError) {
      setError(getErrorMessage(requestError));

      return null;
    } finally {
      setSubmitting(false);
    }
  };

  return { submitting, error, enchant };
};
