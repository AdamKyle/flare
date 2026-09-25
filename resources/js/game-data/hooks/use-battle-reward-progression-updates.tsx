import React, { useCallback, useState } from 'react';

import UseBattleRewardProgressionUpdatesDefinition from './definitions/use-battle-reward-progression-updates-definition';
import UseBattleRewardProgressionUpdatesParamsDefinition from './definitions/use-battle-reward-progression-updates-params-definition';

import BattleRewardProgressionUpdatesWire from 'game-data/components/battle-reward-progression-updates-wire';

export const useBattleRewardProgressionUpdates = (
  params: UseBattleRewardProgressionUpdatesParamsDefinition
): UseBattleRewardProgressionUpdatesDefinition => {
  const { userId, onEvent } = params;

  const [listening, setListening] = useState<boolean>(false);

  const start = useCallback(() => {
    setListening(true);
  }, []);

  const renderWire = () => {
    const hasUser = userId > 0;
    const shouldRender = listening && hasUser;

    if (!shouldRender) {
      return null;
    }

    return (
      <BattleRewardProgressionUpdatesWire userId={userId} onEvent={onEvent} />
    );
  };

  return { listening, start, renderWire };
};
