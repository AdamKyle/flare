import { useState } from 'react';

import UseSeerCampFlowDefinition from './definitions/use-seer-camp-flow-definition';
import SeerCampApiResponseDefinition from '../api/definitions/seer-camp-api-response-definition';
import { useSeerCampApi } from '../api/hooks/use-seer-camp-api';
import { SeerAction } from '../enums/seer-action';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useSeerCampFlow = (): UseSeerCampFlowDefinition => {
  const { gameData, updateCharacter } = useGameData();

  const characterId = gameData?.character?.id ?? 0;
  const goldBars = gameData?.character?.gold_bars ?? 0;

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
    if (action === SeerAction.MANAGE_SOCKETS) {
      updateCharacter({
        gold_bars: Math.max(goldBars - response.costs.socket, 0),
      });
    }

    replaceData(response);
    setStatus(response.message ?? 'The Seer completed your request.');
  };

  return {
    characterId,
    goldBars,
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
