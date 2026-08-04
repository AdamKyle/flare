import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import TrinketryApiResponseDefinition from '../definitions/trinketry-api-response-definition';
import { TrinketryApiUrls } from '../enums/trinketry-api-urls';
import UseTrinketryApiDefinition from './definitions/use-trinketry-api-definition';
import UseTrinketryApiParams from './definitions/use-trinketry-api-params';

export const useTrinketryApi = ({
  characterId,
}: UseTrinketryApiParams): UseTrinketryApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<TrinketryApiResponseDefinition | null>(null);
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
      .get<TrinketryApiResponseDefinition, never>(
        getUrl(TrinketryApiUrls.FETCH, { character: characterId })
      )
      .then(setData)
      .catch((requestError: unknown) =>
        setError(
          requestError instanceof AxiosError
            ? (requestError.response?.data?.message ??
                'Unable to load Trinkets.')
            : 'Unable to load Trinkets.'
        )
      )
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl]);

  return { data, loading, error, replaceData: setData };
};
