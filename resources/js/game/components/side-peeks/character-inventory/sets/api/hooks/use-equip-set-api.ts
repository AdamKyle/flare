import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useState } from 'react';

import UseEquipSetApiDefinition from './definitions/use-equip-set-api-definition';
import UseSetActionApiParams from './definitions/use-set-action-api-params';
import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import SetActionResponseDefinition from '../definitions/set-action-response-definition';
import { resolveSetActionError } from '../utils/resolve-set-action-error';

export const useEquipSetApi = ({
  character_id,
  on_success,
}: UseSetActionApiParams): UseEquipSetApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const equipSet = useCallback(
    async (setId: number): Promise<void> => {
      if (loading) {
        return;
      }

      setLoading(true);
      setError(null);

      try {
        const response = await apiHandler.post<
          SetActionResponseDefinition,
          never,
          Record<string, never>
        >(
          getUrl(CharacterInventoryApiUrls.CHARACTER_EQUIP_SET, {
            character: character_id,
            inventorySet: setId,
          }),
          {}
        );

        on_success(response.message);
      } catch (requestError) {
        setError(
          resolveSetActionError(requestError, 'Unable to equip the set.')
        );

        if (requestError instanceof AxiosError) {
          handleInactivity({ setError, response: requestError });
        }
      } finally {
        setLoading(false);
      }
    },
    [apiHandler, getUrl, character_id, on_success, handleInactivity, loading]
  );

  return { loading, error, equipSet };
};
