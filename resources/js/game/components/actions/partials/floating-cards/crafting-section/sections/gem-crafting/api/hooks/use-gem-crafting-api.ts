import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import GemCraftingApiResponseDefinition from '../definitions/gem-crafting-api-response-definition';
import { GemCraftingApiUrls } from '../enums/gem-crafting-api-urls';
import UseGemCraftingApiDefinition from './definitions/use-gem-crafting-api-definition';
import UseGemCraftingApiParams from './definitions/use-gem-crafting-api-params';

export const useGemCraftingApi = ({
  characterId,
}: UseGemCraftingApiParams): UseGemCraftingApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<GemCraftingApiResponseDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(characterId > 0);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (characterId === 0) {
      setLoading(false);

      return;
    }

    setLoading(true);
    setError(null);
    apiHandler
      .get<GemCraftingApiResponseDefinition, never>(
        getUrl(GemCraftingApiUrls.FETCH_TIERS, { character: characterId })
      )
      .then(setData)
      .catch((requestError: unknown) =>
        setError(
          requestError instanceof AxiosError
            ? (requestError.response?.data?.message ??
                'Unable to load Gem tiers.')
            : 'Unable to load Gem tiers.'
        )
      )
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl]);

  return { data, loading, error, replaceData: setData };
};
