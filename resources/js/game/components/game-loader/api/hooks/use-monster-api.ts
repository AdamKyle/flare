import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosResponse } from 'axios';
import { useCallback } from 'react';

import MonsterDefinition from 'game-data/api-data-definitions/monsters/monster-definition';

export const useMonsterApi = (params: ApiParametersDefinitions) => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const fetchMonstersData = useCallback(async () => {
    try {
      return await apiHandler.get<
        MonsterDefinition[],
        AxiosResponse<MonsterDefinition[]>
      >(url);
    } catch (error) {
      if (error instanceof AxiosError) {
        throw error.response?.data || new Error('Unknown API error');
      }

      throw error;
    }
  }, [apiHandler, url]);

  return { fetchMonstersData };
};
