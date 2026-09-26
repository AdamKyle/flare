import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

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
    const [error, setError] =
      useState<UseGetSetEquippabilityDefinition['error']>(null);
    const [loading, setLoading] = useState(false);
    const [requestParams, setRequestParams] =
      useState<UseGetSetEquippabilityRequestParams>({
        character_id: 0,
        inventory_set_id: 0,
      });

    const abortControllerRef = useRef<AbortController | null>(null);

    useEffect(() => {
      return () => {
        const activeController = abortControllerRef.current;

        abortControllerRef.current = null;
        activeController?.abort();
      };
    }, []);

    const fetchSetEquippabilityDetails =
      useCallback(async (): Promise<void> => {
        if (
          requestParams.character_id === 0 ||
          requestParams.inventory_set_id === 0
        ) {
          return;
        }

        abortControllerRef.current?.abort();
        const controller = new AbortController();
        abortControllerRef.current = controller;

        setLoading(true);
        setError(null);

        try {
          const result = await apiHandler.get<
            UseGetSetEquippabilityResponse[],
            never
          >(
            getUrl(
              CharacterInventoryApiUrls.CHARACTER_SET_EQUIPPABLITY_DETAILS,
              {
                character: requestParams.character_id,
                inventorySet: requestParams.inventory_set_id,
              }
            ),
            { signal: controller.signal }
          );

          if (abortControllerRef.current !== controller) {
            return;
          }

          setData(result);
        } catch (requestError) {
          if (axios.isCancel(requestError)) {
            return;
          }

          if (abortControllerRef.current !== controller) {
            return;
          }

          setError({
            message: resolveApiErrorMessage(
              requestError,
              'Unable to load the set equippability details.'
            ),
          });

          if (requestError instanceof AxiosError) {
            handleInactivity({ response: requestError, setError });
          }
        } finally {
          if (abortControllerRef.current === controller) {
            abortControllerRef.current = null;
            setLoading(false);
          }
        }
      }, [apiHandler, getUrl, handleInactivity, requestParams]);

    useEffect(() => {
      void fetchSetEquippabilityDetails();
    }, [fetchSetEquippabilityDetails]);

    return {
      data,
      loading,
      error,
      setRequestParams,
    };
  };
