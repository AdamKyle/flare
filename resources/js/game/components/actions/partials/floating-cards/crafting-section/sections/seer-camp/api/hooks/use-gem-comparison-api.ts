import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useState } from 'react';

import GemComparisonApiResponseDefinition from '../definitions/gem-comparison-api-response-definition';
import GemComparisonRequestDefinition from '../definitions/gem-comparison-request-definition';
import { SeerCampApiUrls } from '../enums/seer-camp-api-urls';
import UseGemComparisonApiDefinition from './definitions/use-gem-comparison-api-definition';
import UseGemComparisonApiParams from './definitions/use-gem-comparison-api-params';

export const useGemComparisonApi = ({
  characterId,
}: UseGemComparisonApiParams): UseGemComparisonApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const compare = useCallback(
    async (
      request: GemComparisonRequestDefinition
    ): Promise<GemComparisonApiResponseDefinition | null> => {
      setLoading(true);
      setError(null);

      try {
        return await apiHandler.get<
          GemComparisonApiResponseDefinition,
          AxiosRequestConfig<GemComparisonApiResponseDefinition>
        >(getUrl(SeerCampApiUrls.GEM_COMPARISON, { character: characterId }), {
          params: request,
        });
      } catch (requestError) {
        setError(
          requestError instanceof AxiosError
            ? (requestError.response?.data?.message ??
                'Unable to compare the Gem.')
            : 'Unable to compare the Gem.'
        );

        return null;
      } finally {
        setLoading(false);
      }
    },
    [apiHandler, characterId, getUrl]
  );

  return { loading, error, compare };
};
