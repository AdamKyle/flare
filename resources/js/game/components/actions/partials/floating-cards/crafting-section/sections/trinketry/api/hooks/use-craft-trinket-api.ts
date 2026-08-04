import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import CraftTrinketRequestDefinition from '../definitions/craft-trinket-request-definition';
import TrinketryApiResponseDefinition from '../definitions/trinketry-api-response-definition';
import { TrinketryApiUrls } from '../enums/trinketry-api-urls';
import UseCraftTrinketApiDefinition from './definitions/use-craft-trinket-api-definition';
import UseCraftTrinketApiParams from './definitions/use-craft-trinket-api-params';

export const useCraftTrinketApi = ({
  characterId,
  itemId,
}: UseCraftTrinketApiParams): UseCraftTrinketApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [crafting, setCrafting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const craft = async (): Promise<TrinketryApiResponseDefinition | null> => {
    if (!itemId) {
      return null;
    }

    setCrafting(true);
    setError(null);

    try {
      const request: CraftTrinketRequestDefinition = {
        item_to_craft: itemId,
      };

      return await apiHandler.post<
        TrinketryApiResponseDefinition,
        never,
        CraftTrinketRequestDefinition
      >(getUrl(TrinketryApiUrls.CRAFT, { character: characterId }), request);
    } catch (requestError) {
      setError(
        requestError instanceof AxiosError
          ? (requestError.response?.data?.message ??
              'Unable to craft the Trinket.')
          : 'Unable to craft the Trinket.'
      );

      return null;
    } finally {
      setCrafting(false);
    }
  };

  return { crafting, error, craft };
};
