import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import UseActiveBoonsActionsDefinition from './definitions/use-active-boons-actions-definition';
import UseActiveBoonsActionsParams from './definitions/use-active-boons-actions-params';
import ActiveBoonActionResponseDefinition from '../definitions/active-boon-action-response-definition';
import { ActiveBoonsApiUrls } from '../enums/active-boons-api-urls';

import { useGameData } from 'game-data/hooks/use-game-data';

const errorMessage = (error: unknown, fallback: string): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ?? fallback)
    : fallback;

export const useActiveBoonsActions = ({
  characterId,
}: UseActiveBoonsActionsParams): UseActiveBoonsActionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { updateCharacter } = useGameData();

  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [mutationError, setMutationError] = useState<string | null>(null);
  const [fillingBoonId, setFillingBoonId] = useState<number | null>(null);
  const [removingBoonId, setRemovingBoonId] = useState<number | null>(null);

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

      updateCharacter({ active_boons: response.boons });
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

      updateCharacter({ active_boons: response.boons });
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
    successMessage,
    mutationError,
    fillingBoonId,
    removingBoonId,
    fillUpBoon,
    removeBoon,
  };
};
