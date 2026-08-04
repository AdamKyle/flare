import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import AlchemyApiResponseDefinition from '../definitions/alchemy-api-response-definition';
import { AlchemyApiUrls } from '../enums/alchemy-api-urls';
import UseAlchemyApiDefinition from './definitions/use-alchemy-api-definition';
import UseAlchemyApiParams from './definitions/use-alchemy-api-params';

const errorMessage = (error: unknown): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ?? 'Unable to load Alchemy items.')
    : 'Unable to load Alchemy items.';

export const useAlchemyApi = ({
  characterId,
}: UseAlchemyApiParams): UseAlchemyApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<AlchemyApiResponseDefinition | null>(null);
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
      .get<AlchemyApiResponseDefinition, never>(
        getUrl(AlchemyApiUrls.FETCH, { character: characterId })
      )
      .then(setData)
      .catch((requestError: unknown) => setError(errorMessage(requestError)))
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl]);

  return { data, loading, error, replaceData: setData };
};
