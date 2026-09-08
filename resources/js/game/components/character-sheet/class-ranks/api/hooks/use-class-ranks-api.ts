import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import UseClassRanksApiDefinition from './definitions/use-class-ranks-api-definition';
import UseClassRanksApiParams from './definitions/use-class-ranks-api-params';
import ClassRankDefinition from '../definitions/class-rank-definition';
import ClassRanksResponseDefinition from '../definitions/class-ranks-response-definition';
import { ClassRanksApiUrls } from '../enums/class-ranks-api-urls';

const errorMessage = (error: unknown, fallback: string): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ?? fallback)
    : fallback;

export const useClassRanksApi = ({
  characterId,
}: UseClassRanksApiParams): UseClassRanksApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<ClassRankDefinition[]>([]);
  const [loading, setLoading] = useState(characterId > 0);
  const [error, setError] = useState<string | null>(null);
  const [switchingClassId, setSwitchingClassId] = useState<number | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [mutationError, setMutationError] = useState<string | null>(null);

  useEffect(() => {
    if (characterId <= 0) {
      setLoading(false);

      return;
    }

    setLoading(true);
    setError(null);

    apiHandler
      .get<ClassRanksResponseDefinition, never>(
        getUrl(ClassRanksApiUrls.RANKS, { character: characterId })
      )
      .then((response) => setData(response.class_ranks))
      .catch((requestError: unknown) =>
        setError(errorMessage(requestError, 'Unable to load Class Ranks.'))
      )
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl]);

  const switchClass = async (gameClassId: number): Promise<void> => {
    if (characterId <= 0) {
      return;
    }

    setSwitchingClassId(gameClassId);
    setSuccessMessage(null);
    setMutationError(null);

    try {
      const response = await apiHandler.post<
        ClassRanksResponseDefinition,
        never,
        Record<string, never>
      >(
        getUrl(ClassRanksApiUrls.SWITCH_CLASS, {
          character: characterId,
          gameClass: gameClassId,
        }),
        {}
      );

      setData(response.class_ranks);
      setSuccessMessage(response.message ?? null);
    } catch (requestError) {
      setMutationError(errorMessage(requestError, 'Unable to switch classes.'));
    } finally {
      setSwitchingClassId(null);
    }
  };

  return {
    data,
    loading,
    error,
    switchingClassId,
    successMessage,
    mutationError,
    switchClass,
  };
};
