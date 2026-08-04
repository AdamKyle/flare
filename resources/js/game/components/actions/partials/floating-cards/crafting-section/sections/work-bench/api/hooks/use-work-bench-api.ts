import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import WorkBenchApiResponseDefinition from '../definitions/work-bench-api-response-definition';
import { WorkBenchApiUrls } from '../enums/work-bench-api-urls';
import UseWorkBenchApiDefinition from './definitions/use-work-bench-api-definition';
import UseWorkBenchApiParams from './definitions/use-work-bench-api-params';
export const useWorkBenchApi = ({
  characterId,
}: UseWorkBenchApiParams): UseWorkBenchApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<WorkBenchApiResponseDefinition | null>(null);
  const [loading, setLoading] = useState(characterId > 0);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    if (characterId === 0) {
      setLoading(false);
      return;
    }
    setLoading(true);
    apiHandler
      .get<WorkBenchApiResponseDefinition, never>(
        getUrl(WorkBenchApiUrls.FETCH, { character: characterId })
      )
      .then(setData)
      .catch((requestError: unknown) =>
        setError(
          requestError instanceof AxiosError
            ? (requestError.response?.data?.message ??
                'Unable to load the Work Bench.')
            : 'Unable to load the Work Bench.'
        )
      )
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl]);
  return { data, loading, error, replaceData: setData };
};
