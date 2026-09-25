import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import UseBeginExplorationApiDefinition from './definitions/use-begin-exploration-api-definition';
import UseBeginExplorationApiParams from './definitions/use-begin-exploration-api-params';
import UseBeginExplorationRequestParamsDefinition from './definitions/use-begin-exploration-request-params-definition';
import BeginExplorationRequestDefinition from '../definitions/begin-exploration-request-definition';
import BeginExplorationResponseDefinition from '../definitions/begin-exploration-response-definition';
import { ExplorationApiUrls } from '../enums/exploration-api-urls';

const UNABLE_TO_BEGIN_EXPLORATION_MESSAGE =
  'Unable to start Exploration. Please try again.';

const useBeginExplorationApi = (
  params: UseBeginExplorationApiParams
): UseBeginExplorationApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] =
    useState<UseBeginExplorationApiDefinition['error']>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [explorationMessage, setExplorationMessage] =
    useState<UseBeginExplorationApiDefinition['explorationMessage']>(null);
  const [requestParams, setRequestParams] =
    useState<UseBeginExplorationRequestParamsDefinition>({
      selected_monster_id: null,
      auto_attack_length: null,
      attack_type: null,
    });

  const url = getUrl(ExplorationApiUrls.BEGIN_EXPLORATION, {
    character: params.character_id,
  });

  const beginExploration = useCallback(async () => {
    if (
      requestParams.selected_monster_id === null ||
      requestParams.auto_attack_length === null ||
      requestParams.attack_type === null
    ) {
      return;
    }

    setLoading(true);
    setError(null);
    setExplorationMessage(null);

    try {
      const result = await apiHandler.post<
        BeginExplorationResponseDefinition,
        AxiosRequestConfig<AxiosResponse<BeginExplorationResponseDefinition>>,
        BeginExplorationRequestDefinition
      >(url, {
        selected_monster_id: requestParams.selected_monster_id,
        auto_attack_length: requestParams.auto_attack_length,
        attack_type: requestParams.attack_type,
      });

      setSuccessMessage(result.message);
      setExplorationMessage(result.exploration_message);
    } catch (err) {
      if (!(err instanceof AxiosError)) {
        setError({ message: UNABLE_TO_BEGIN_EXPLORATION_MESSAGE });
      } else if (err.response?.status === 401) {
        handleInactivity({ setError, response: err });
      } else {
        setError(
          err.response?.data ?? {
            message: UNABLE_TO_BEGIN_EXPLORATION_MESSAGE,
          }
        );
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, url, requestParams, handleInactivity]);

  useEffect(() => {
    void beginExploration();
  }, [beginExploration]);

  return {
    loading,
    error,
    successMessage,
    explorationMessage,
    setRequestParams,
  };
};

export default useBeginExplorationApi;
