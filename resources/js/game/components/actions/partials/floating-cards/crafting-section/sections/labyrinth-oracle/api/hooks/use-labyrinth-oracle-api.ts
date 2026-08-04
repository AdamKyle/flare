import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import LabyrinthOracleApiResponseDefinition from '../definitions/labyrinth-oracle-api-response-definition';
import { LabyrinthOracleApiUrls } from '../enums/labyrinth-oracle-api-urls';
import UseLabyrinthOracleApiDefinition from './definitions/use-labyrinth-oracle-api-definition';
import UseLabyrinthOracleApiParams from './definitions/use-labyrinth-oracle-api-params';

export const useLabyrinthOracleApi = ({
  characterId,
}: UseLabyrinthOracleApiParams): UseLabyrinthOracleApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<LabyrinthOracleApiResponseDefinition | null>(
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
      .get<LabyrinthOracleApiResponseDefinition, never>(
        getUrl(LabyrinthOracleApiUrls.FETCH, { character: characterId })
      )
      .then(setData)
      .catch((requestError: unknown) =>
        setError(
          requestError instanceof AxiosError
            ? (requestError.response?.data?.message ??
                'Unable to visit the Labyrinth Oracle.')
            : 'Unable to visit the Labyrinth Oracle.'
        )
      )
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl]);
  return { data, loading, error, replaceData: setData };
};
