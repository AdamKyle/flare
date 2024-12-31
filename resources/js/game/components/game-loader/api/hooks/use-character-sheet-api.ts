import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosResponse } from 'axios';
import { useCallback } from 'react';

import UseCharacterSheetApiDefinition from './definitions/use-character-sheet-api-definition';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export const useCharacterSheetApi = (
  params: ApiParametersDefinitions
): UseCharacterSheetApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const fetchCharacterData = useCallback(async () => {
    try {
      return await apiHandler.get<
        CharacterSheetDefinition,
        AxiosResponse<CharacterSheetDefinition>
      >(url);
    } catch (error) {
      if (error instanceof AxiosError) {
        throw error.response?.data || new Error('Unknown API error');
      }
      throw error;
    }
  }, [apiHandler, url]);

  return { fetchCharacterData };
};
