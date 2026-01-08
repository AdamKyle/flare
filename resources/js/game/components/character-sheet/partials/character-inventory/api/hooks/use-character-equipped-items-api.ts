import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import CharacterInventoryItemDetails from './definitions/character-equipped-items-api-definitions';
import UseCharacterEquippedApiDefinition, {
  CharacterEquippedDefinition,
} from './types/use-character-equipped-api-definition';

const useCharacterEquippedItemsApi = (
  params: ApiParametersDefinitions
): UseCharacterEquippedApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const [data, setData] = useState<CharacterEquippedDefinition | null>(null);
  const [error, setError] =
    useState<UseCharacterEquippedApiDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  const fetchCharacterEquippedItems = useCallback(async () => {
    try {
      const result = await apiHandler.get<
        CharacterInventoryItemDetails,
        AxiosRequestConfig<AxiosResponse<CharacterInventoryItemDetails>>
      >(url);

      setData({
        equipped_items: result.equipped.data,
        weapon_damage: result.weapon_damage,
        spell_damage: result.spell_damage,
        healing_amount: result.healing_amount,
        defence_amount: result.defence_amount,
      });
    } catch (error) {
      if (error instanceof AxiosError) {
        setError(error.response?.data || null);
      } else {
        setError(null);
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, url]);

  useEffect(() => {
    fetchCharacterEquippedItems().catch(console.error);
  }, [fetchCharacterEquippedItems]);

  return {
    data,
    error,
    loading,
  };
};

export default useCharacterEquippedItemsApi;
