import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useState } from 'react';

import UseRemoveItemFromSetApiDefinition from './definitions/use-remove-item-from-set-api-definition';
import UseSetActionApiParams from './definitions/use-set-action-api-params';
import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import RemoveItemFromSetRequestDefinition from '../definitions/remove-item-from-set-request-definition';
import SetActionResponseDefinition from '../definitions/set-action-response-definition';
import { resolveSetActionError } from '../utils/resolve-set-action-error';

export const useRemoveItemFromSetApi = ({
  character_id,
  on_success,
}: UseSetActionApiParams): UseRemoveItemFromSetApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const removeItemFromSet = useCallback(
    async (request: RemoveItemFromSetRequestDefinition): Promise<void> => {
      if (loading) {
        return;
      }

      setLoading(true);
      setError(null);

      try {
        const response = await apiHandler.post<
          SetActionResponseDefinition,
          never,
          RemoveItemFromSetRequestDefinition
        >(
          getUrl(CharacterInventoryApiUrls.CHARACTER_REMOVE_FROM_SET, {
            character: character_id,
          }),
          request
        );

        on_success(response.message);
      } catch (requestError) {
        setError(
          resolveSetActionError(
            requestError,
            'Unable to remove the item from the set.'
          )
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

  return { loading, error, removeItemFromSet };
};
