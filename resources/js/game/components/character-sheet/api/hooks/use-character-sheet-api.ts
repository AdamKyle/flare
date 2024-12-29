import { AxiosError, AxiosResponse } from 'axios';
import { useEffect, useState } from 'react';

import { useApiHandler } from '../../../../../axios/hooks/use-api-handler';
import CharacterSheetDefinition from '../definitions/character-api-definitions/character-sheet-definition';
import UseCharacterSheetApiDefinition from '../definitions/use-character-sheet-api-definition';
import UseCharacterSheetApiParameters from '../definitions/use-character-sheet-api-paramters';
import UseCharacterSheetApiState from '../types/use-character-sheet-api-state';

export const useCharacterSheetApi = (
  params: UseCharacterSheetApiParameters
): UseCharacterSheetApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<UseCharacterSheetApiState['data']>(null);
  const [error, setError] = useState<UseCharacterSheetApiState['error']>(null);
  const [loading, setLoading] =
    useState<UseCharacterSheetApiState['loading']>(true);

  const url = getUrl(params.url, params.urlParams);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const response = await apiHandler.get<
          CharacterSheetDefinition,
          AxiosResponse<CharacterSheetDefinition>
        >(url);
        setData(response);
      } catch (error) {
        setError(
          error instanceof AxiosError
            ? error.response?.data
            : new Error('Unknown error')
        );
      } finally {
        setLoading(false);
      }
    };

    fetchData().catch((error) => {
      console.error('Error in fetchData:', error);
    });
  }, [apiHandler, url]);

  return { data, error, loading };
};
