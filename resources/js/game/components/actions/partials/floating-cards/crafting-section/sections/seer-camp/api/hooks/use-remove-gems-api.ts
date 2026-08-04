import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useState } from 'react';

import RemoveAllGemsApiResponseDefinition from '../definitions/remove-all-gems-api-response-definition';
import { SeerCampApiUrls } from '../enums/seer-camp-api-urls';
import UseRemoveGemsApiDefinition from './definitions/use-remove-gems-api-definition';
import UseRemoveGemsApiParams from './definitions/use-remove-gems-api-params';
export const useRemoveGemsApi = ({
  characterId,
  inventorySlotId,
}: UseRemoveGemsApiParams): UseRemoveGemsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const removeAll = useCallback(async () => {
    if (inventorySlotId === null) return null;
    setSubmitting(true);
    setError(null);
    try {
      return await apiHandler.post<
        RemoveAllGemsApiResponseDefinition,
        never,
        Record<string, never>
      >(
        getUrl(SeerCampApiUrls.REMOVE_ALL_GEMS, {
          character: characterId,
          inventorySlot: inventorySlotId,
        }),
        {}
      );
    } catch (requestError) {
      setError(
        requestError instanceof AxiosError
          ? (requestError.response?.data?.message ??
              'Unable to remove the Gems.')
          : 'Unable to remove the Gems.'
      );
      return null;
    } finally {
      setSubmitting(false);
    }
  }, [apiHandler, characterId, getUrl, inventorySlotId]);
  return { submitting, error, removeAll };
};
