import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import MoveQueenAffixesRequestDefinition from '../definitions/move-queen-affixes-request-definition';
import QueenOfHeartsApiResponseDefinition from '../definitions/queen-of-hearts-api-response-definition';
import { QueenOfHeartsApiUrls } from '../enums/queen-of-hearts-api-urls';
import UseMoveQueenAffixesApiDefinition from './definitions/use-move-queen-affixes-api-definition';
import UseMoveQueenAffixesApiParams from './definitions/use-move-queen-affixes-api-params';

export const useMoveQueenAffixesApi = ({
  characterId,
  request,
}: UseMoveQueenAffixesApiParams): UseMoveQueenAffixesApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const move = async (): Promise<QueenOfHeartsApiResponseDefinition | null> => {
    if (!request) return null;
    setSubmitting(true);
    setError(null);
    try {
      return await apiHandler.post<
        QueenOfHeartsApiResponseDefinition,
        never,
        MoveQueenAffixesRequestDefinition
      >(
        getUrl(QueenOfHeartsApiUrls.MOVE_AFFIXES, { character: characterId }),
        request
      );
    } catch (requestError) {
      setError(
        requestError instanceof AxiosError
          ? (requestError.response?.data?.message ??
              'Unable to move the enchantments.')
          : 'Unable to move the enchantments.'
      );
      return null;
    } finally {
      setSubmitting(false);
    }
  };

  return { submitting, error, move };
};
