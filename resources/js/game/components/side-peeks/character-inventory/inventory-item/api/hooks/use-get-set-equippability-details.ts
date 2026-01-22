import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import UseGetSetEquippabilityDefinition from '../definitions/use-get-set-equippability-definition';
import UseGetSetEquippabilityRequestParams from '../definitions/use-get-set-equippability-request-params';
import UseGetSetEquippabilityResponse from '../definitions/use-get-set-equippability-response-definition';

export const UseGetSetEquippabilityDetails =
  (): UseGetSetEquippabilityDefinition => {
    const { apiHandler, getUrl } = useApiHandler();
    const { handleInactivity } = useActivityTimeout();

    const [data, setData] = useState<UseGetSetEquippabilityResponse[] | null>(
      null
    );
    const [error, setError] = useState<
      UseGetSetEquippabilityDefinition['error'] | null
    >(null);
    const [loading, setLoading] = useState(false);
    const [requestParams, setRequestParams] =
      useState<UseGetSetEquippabilityRequestParams>({
        character_id: 0,
        inventory_set_id: 0,
      });

    const fetchSetEquippabilityDetails = useCallback(async () => {
      if (
        requestParams.character_id === 0 ||
        requestParams.inventory_set_id === 0
      ) {
        return;
      }

      const url = getUrl(
        CharacterInventoryApiUrls.CHARACTER_SET_EQUIPPABLITY_DETAILS,
        {
          character: requestParams.character_id,
          inventorySet: requestParams.inventory_set_id,
        }
      );

      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.get<
          UseGetSetEquippabilityResponse[],
          AxiosRequestConfig<AxiosResponse<UseGetSetEquippabilityResponse[]>>
        >(url);

        setData(result);
      } catch (err) {
        if (err instanceof AxiosError) {
          handleInactivity({
            setError: setError,
            response: err,
          });

          setError(err.response?.data || null);
        }
      } finally {
        setLoading(false);
      }
    }, [requestParams]);

    useEffect(() => {
      fetchSetEquippabilityDetails().catch(() => {});
    }, [fetchSetEquippabilityDetails]);

    return {
      data,
      loading,
      error,
      setRequestParams,
    };
  };
