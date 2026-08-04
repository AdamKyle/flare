import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import QueenOfHeartsApiResponseDefinition from '../definitions/queen-of-hearts-api-response-definition';
import { QueenOfHeartsApiUrls } from '../enums/queen-of-hearts-api-urls';
import UseQueenOfHeartsApiDefinition from './definitions/use-queen-of-hearts-api-definition';
import UseQueenOfHeartsApiParams from './definitions/use-queen-of-hearts-api-params';

export const useQueenOfHeartsApi = ({
  characterId,
}: UseQueenOfHeartsApiParams): UseQueenOfHeartsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<QueenOfHeartsApiResponseDefinition | null>(
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
      .get<QueenOfHeartsApiResponseDefinition, never>(
        getUrl(QueenOfHeartsApiUrls.FETCH_ITEMS, { character: characterId })
      )
      .then(setData)
      .catch((requestError: unknown) =>
        setError(
          requestError instanceof AxiosError
            ? (requestError.response?.data?.message ??
                'Unable to visit the Queen of Hearts.')
            : 'Unable to visit the Queen of Hearts.'
        )
      )
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl]);

  return { data, loading, error, replaceData: setData };
};
