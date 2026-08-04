import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import QueenOfHeartsApiResponseDefinition from '../definitions/queen-of-hearts-api-response-definition';
import RerollQueenAffixRequestDefinition from '../definitions/reroll-queen-affix-request-definition';
import { QueenOfHeartsApiUrls } from '../enums/queen-of-hearts-api-urls';
import UseRerollQueenAffixApiDefinition from './definitions/use-reroll-queen-affix-api-definition';
import UseRerollQueenAffixApiParams from './definitions/use-reroll-queen-affix-api-params';

export const useRerollQueenAffixApi = ({
  characterId,
  request,
}: UseRerollQueenAffixApiParams): UseRerollQueenAffixApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const reroll =
    async (): Promise<QueenOfHeartsApiResponseDefinition | null> => {
      if (!request) return null;
      setSubmitting(true);
      setError(null);
      try {
        return await apiHandler.post<
          QueenOfHeartsApiResponseDefinition,
          never,
          RerollQueenAffixRequestDefinition
        >(
          getUrl(QueenOfHeartsApiUrls.REROLL, { character: characterId }),
          request
        );
      } catch (requestError) {
        setError(
          requestError instanceof AxiosError
            ? (requestError.response?.data?.message ??
                'Unable to re roll the item.')
            : 'Unable to re roll the item.'
        );
        return null;
      } finally {
        setSubmitting(false);
      }
    };

  return { submitting, error, reroll };
};
