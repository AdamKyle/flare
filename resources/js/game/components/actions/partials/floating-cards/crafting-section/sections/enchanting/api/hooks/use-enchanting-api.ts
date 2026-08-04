import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import EnchantingApiResponseDefinition from '../definitions/enchanting-api-response-definition';
import { EnchantingApiUrls } from '../enums/enchanting-api-urls';
import UseEnchantingApiDefinition from './definitions/use-enchanting-api-definition';
import UseEnchantingApiParams from './definitions/use-enchanting-api-params';
export const useEnchantingApi = ({
  characterId,
}: UseEnchantingApiParams): UseEnchantingApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<EnchantingApiResponseDefinition | null>(
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
    apiHandler
      .get<EnchantingApiResponseDefinition, never>(
        getUrl(EnchantingApiUrls.FETCH, { character: characterId })
      )
      .then(setData)
      .catch((requestError: unknown) =>
        setError(
          requestError instanceof AxiosError
            ? (requestError.response?.data?.message ??
                'Unable to load Enchanting.')
            : 'Unable to load Enchanting.'
        )
      )
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl]);
  return { data, loading, error, replaceData: setData };
};
