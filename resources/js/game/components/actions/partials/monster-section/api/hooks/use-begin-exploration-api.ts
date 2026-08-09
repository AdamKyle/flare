import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import UseBeginExplorationApiDefinition from './definitions/use-begin-exploration-api-definition';
import UseBeginExplorationApiParams from './definitions/use-begin-exploration-api-params';
import UseBeginExplorationRequestParamsDefinition from './definitions/use-begin-exploration-request-params-definition';
import { ExplorationApiUrls } from '../enums/exploration-api-urls';
import BeginExplorationRequestDefinition from '../definitions/begin-exploration-request-definition';
import BeginExplorationResponseDefinition from '../definitions/begin-exploration-response-definition';

const useBeginExplorationApi = (
  params: UseBeginExplorationApiParams
): UseBeginExplorationApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] =
    useState<UseBeginExplorationApiDefinition['error']>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [requestParams, setRequestParams] =
    useState<UseBeginExplorationRequestParamsDefinition>({
      auto_attack_length: null,
      attack_type: null,
    });

  const url = getUrl(ExplorationApiUrls.BEGIN_EXPLORATION, {
    character: params.character_id,
  });

  const beginExploration = useCallback(async () => {
    if (
      requestParams.auto_attack_length === null ||
      requestParams.attack_type === null
    ) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.post<
        BeginExplorationResponseDefinition,
        AxiosRequestConfig<AxiosResponse<BeginExplorationResponseDefinition>>,
        BeginExplorationRequestDefinition
      >(url, {
        auto_attack_length: requestParams.auto_attack_length,
        attack_type: requestParams.attack_type,
      });

      setSuccessMessage(result.message);
    } catch (err) {
      if (err instanceof AxiosError) {
        handleInactivity({
          setError,
          response: err,
        });

        setError(err.response?.data || null);
      }
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [apiHandler, url, requestParams]);

  useEffect(() => {
    beginExploration().catch(() => {});
  }, [beginExploration]);

  return {
    loading,
    error,
    successMessage,
    setRequestParams,
  };
};

export default useBeginExplorationApi;
