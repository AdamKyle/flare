import { useState } from 'react';

import UseSeerCampFlowDefinition from './definitions/use-seer-camp-flow-definition';
import SeerCampApiResponseDefinition from '../api/definitions/seer-camp-api-response-definition';
import { useSeerCampApi } from '../api/hooks/use-seer-camp-api';
import { SeerAction } from '../enums/seer-action';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useSeerCampFlow = (): UseSeerCampFlowDefinition => {
  const { gameData } = useGameData();

  const characterId = gameData?.character?.id ?? 0;

  const {
    data,
    loading,
    error,
    removalLoading,
    removalError,
    replaceData,
    fetchRemovalData,
  } = useSeerCampApi({ characterId });

  const [action, setAction] = useState<SeerAction | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const selectAction = (nextAction: SeerAction): void => {
    setAction(nextAction);
    setStatus(null);

    if (nextAction === SeerAction.REMOVE_GEM) {
      void fetchRemovalData();
    }
  };

  const changeAction = (): void => {
    setAction(null);
    setStatus(null);
  };

  const handleActionSuccess = (
    response: SeerCampApiResponseDefinition
  ): void => {
    replaceData(response);
    setStatus(response.message ?? 'The Seer completed your request.');
  };

  return {
    characterId,
    data,
    loading,
    error,
    removalLoading,
    removalError,
    action,
    status,
    selectAction,
    changeAction,
    handleActionSuccess,
  };
};
