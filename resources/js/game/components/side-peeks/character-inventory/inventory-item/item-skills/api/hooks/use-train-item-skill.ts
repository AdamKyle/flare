import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useState } from 'react';

import { ItemSkillApiUrls } from '../../enums/item-skill-api-urls';
import ItemSkillMutationHook from '../types/item-skill-mutation-hook';
import ItemSkillMutationResponse from '../types/item-skill-mutation-response';

export const useTrainItemSkill = (
  characterId: number,
  itemId: number
): ItemSkillMutationHook => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<ItemSkillMutationHook['error']>(null);

  const mutate = useCallback(
    async (itemSkillProgressionId: number): Promise<string | null> => {
      setLoading(true);
      setError(null);

      try {
        const response = await apiHandler.post<
          ItemSkillMutationResponse,
          AxiosRequestConfig<Record<string, never>>,
          Record<string, never>
        >(
          getUrl(ItemSkillApiUrls.TRAIN, {
            character: characterId,
            itemId,
            itemSkillProgressionId,
          }),
          {}
        );

        return response.message;
      } catch (caughtError) {
        if (caughtError instanceof AxiosError) {
          handleInactivity({ setError, response: caughtError });
          setError(caughtError.response?.data || null);
        }

        return null;
      } finally {
        setLoading(false);
      }
    },
    [apiHandler, characterId, getUrl, handleInactivity, itemId]
  );

  return { mutate, loading, error, reset_error: () => setError(null) };
};
