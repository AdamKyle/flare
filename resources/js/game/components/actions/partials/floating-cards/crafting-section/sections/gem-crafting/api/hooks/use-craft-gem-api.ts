import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import CraftGemRequestDefinition from '../definitions/craft-gem-request-definition';
import GemCraftingApiResponseDefinition from '../definitions/gem-crafting-api-response-definition';
import { GemCraftingApiUrls } from '../enums/gem-crafting-api-urls';
import UseCraftGemApiDefinition from './definitions/use-craft-gem-api-definition';
import UseCraftGemApiParams from './definitions/use-craft-gem-api-params';

export const useCraftGemApi = ({
  characterId,
}: UseCraftGemApiParams): UseCraftGemApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [crafting, setCrafting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const craft = async (
    tier: number
  ): Promise<GemCraftingApiResponseDefinition | null> => {
    setCrafting(true);
    setError(null);

    try {
      return await apiHandler.post<
        GemCraftingApiResponseDefinition,
        never,
        CraftGemRequestDefinition
      >(getUrl(GemCraftingApiUrls.CRAFT, { character: characterId }), {
        tier,
      });
    } catch (requestError) {
      setError(
        requestError instanceof AxiosError
          ? (requestError.response?.data?.message ?? 'Unable to craft the Gem.')
          : 'Unable to craft the Gem.'
      );

      return null;
    } finally {
      setCrafting(false);
    }
  };

  return { crafting, error, craft };
};
