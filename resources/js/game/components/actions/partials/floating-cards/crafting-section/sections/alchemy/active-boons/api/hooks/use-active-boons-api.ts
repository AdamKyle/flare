import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useState } from 'react';

import UseActiveBoonsApiDefinition from './definitions/use-active-boons-api-definition';
import UseActiveBoonsApiParams from './definitions/use-active-boons-api-params';
import ActiveBoonActionResponseDefinition from '../definitions/active-boon-action-response-definition';
import ActiveBoonDefinition from '../definitions/active-boon-definition';
import ActiveBoonsResponseDefinition from '../definitions/active-boons-response-definition';
import { ActiveBoonsApiUrls } from '../enums/active-boons-api-urls';

const errorMessage = (error: unknown, fallback: string): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ?? fallback)
    : fallback;

export const useActiveBoonsApi = ({
  characterId,
}: UseActiveBoonsApiParams): UseActiveBoonsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [boons, setBoons] = useState<ActiveBoonDefinition[]>([]);
  const [loading, setLoading] = useState(characterId > 0);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [mutationError, setMutationError] = useState<string | null>(null);
  const [fillingBoonId, setFillingBoonId] = useState<number | null>(null);
  const [removingBoonId, setRemovingBoonId] = useState<number | null>(null);
  const [refreshToggle, setRefreshToggle] = useState(false);

  useEffect(() => {
    if (characterId <= 0) {
      setLoading(false);

      return;
    }

    setLoading(true);
    setError(null);

    apiHandler
      .get<ActiveBoonsResponseDefinition, never>(
        getUrl(ActiveBoonsApiUrls.ACTIVE_BOONS, { character: characterId })
      )
      .then((response) => setBoons(response.active_boons))
      .catch((requestError: unknown) =>
        setError(errorMessage(requestError, 'Unable to load Active Boons.'))
      )
      .finally(() => setLoading(false));
  }, [apiHandler, characterId, getUrl, refreshToggle]);

  const refresh = (): void => {
    setRefreshToggle((previousValue) => !previousValue);
  };

  const replaceBoons = (updatedBoons: ActiveBoonDefinition[]): void => {
    setBoons(updatedBoons);
  };

  const fillUpBoon = async (boonId: number): Promise<void> => {
    if (characterId <= 0) {
      return;
    }

    setFillingBoonId(boonId);
    setSuccessMessage(null);
    setMutationError(null);

    try {
      const response = await apiHandler.post<
        ActiveBoonActionResponseDefinition,
        never,
        Record<string, never>
      >(
        getUrl(ActiveBoonsApiUrls.FILL_UP_BOON, {
          character: characterId,
          boon: boonId,
        }),
        {}
      );

      setBoons(response.boons);
      setSuccessMessage(response.message);
    } catch (requestError) {
      setMutationError(
        errorMessage(requestError, 'Unable to fill up the boon.')
      );
    } finally {
      setFillingBoonId(null);
    }
  };

  const removeBoon = async (boonId: number): Promise<void> => {
    if (characterId <= 0) {
      return;
    }

    setRemovingBoonId(boonId);
    setSuccessMessage(null);
    setMutationError(null);

    try {
      const response = await apiHandler.post<
        ActiveBoonActionResponseDefinition,
        never,
        Record<string, never>
      >(
        getUrl(ActiveBoonsApiUrls.REMOVE_BOON, {
          character: characterId,
          boon: boonId,
        }),
        {}
      );

      setBoons(response.boons);
      setSuccessMessage(response.message);
    } catch (requestError) {
      setMutationError(
        errorMessage(requestError, 'Unable to remove the boon.')
      );
    } finally {
      setRemovingBoonId(null);
    }
  };

  return {
    boons,
    loading,
    error,
    successMessage,
    mutationError,
    fillingBoonId,
    removingBoonId,
    refresh,
    replaceBoons,
    fillUpBoon,
    removeBoon,
  };
};
