import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useState, useEffect, useCallback } from 'react';

import FetchCharacterStatInfoApiParams from './definitions/fetch-character-stat-info-api-params';
import UseGetCharacterStatBreakDownParams from './definitions/use-get-character-stat-break-down-params';
import UseCharacterStatBreakDownState from './types/use-character-stat-break-down-state';
import CharacterStatBreakDownDefinition from '../../api-definitions/character-stat-break-down-definition';
import { characterStatParamBuilder } from '../param-builders/character-stat-param-builder';

export const useGetCharacterStatBreakDown = (
  params: UseGetCharacterStatBreakDownParams
) => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const [data, setData] =
    useState<UseCharacterStatBreakDownState['data']>(null);
  const [error, setError] =
    useState<UseCharacterStatBreakDownState['error']>(null);
  const [loading, setLoading] =
    useState<UseCharacterStatBreakDownState['loading']>(true);

  const fetchCharacterStatInfo = useCallback(
    async ({ statType }: FetchCharacterStatInfoApiParams) => {
      const requestParams = characterStatParamBuilder(statType);

      try {
        const result = await apiHandler.get<
          CharacterStatBreakDownDefinition,
          AxiosRequestConfig<CharacterStatBreakDownDefinition>
        >(url, { params: requestParams });
        setData(result);
      } catch (error) {
        if (error instanceof AxiosError) {
          setError(error.response?.data);
        } else {
          throw error;
        }
      } finally {
        setLoading(false);
      }
    },
    [apiHandler, url]
  );

  useEffect(
    () => {
      fetchCharacterStatInfo({ statType: params.statType }).catch((error) =>
        console.error(error)
      );
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [fetchCharacterStatInfo]
  );

  return { data, error, loading };
};
