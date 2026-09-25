import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useState } from 'react';

import UseSetActionApiParams from './definitions/use-set-action-api-params';
import UseUnequipSetApiDefinition from './definitions/use-unequip-set-api-definition';
import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import SetActionResponseDefinition from '../definitions/set-action-response-definition';
import UnequipSetRequestDefinition from '../definitions/unequip-set-request-definition';
import { resolveSetActionError } from '../utils/resolve-set-action-error';

export const useUnequipSetApi = ({
  character_id,
  on_success,
}: UseSetActionApiParams): UseUnequipSetApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const unequipSet = useCallback(async (): Promise<void> => {
    if (loading) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await apiHandler.post<
        SetActionResponseDefinition,
        never,
        UnequipSetRequestDefinition
      >(
        getUrl(CharacterInventoryApiUrls.CHARACTER_UNEQUIP, {
          character: character_id,
        }),
        { inventory_set_equipped: true }
      );

      on_success(response.message);
    } catch (requestError) {
      setError(
        resolveSetActionError(requestError, 'Unable to unequip the set.')
      );

      if (requestError instanceof AxiosError) {
        handleInactivity({ setError, response: requestError });
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, getUrl, character_id, on_success, handleInactivity, loading]);

  return { loading, error, unequipSet };
};
