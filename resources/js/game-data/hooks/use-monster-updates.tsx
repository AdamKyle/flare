import React, { useCallback, useState } from 'react';

import UseMonsterUpdatesDefinition from './definitions/use-monster-updates-definition';

import MonsterUpdatesWire from 'game-data/components/monster-updates-wire';
import UseMonsterUpdatesParams from 'game-data/hooks/definitions/use-monster-update-params-definition';

const useMonsterUpdates = (
  params: UseMonsterUpdatesParams
): UseMonsterUpdatesDefinition => {
  const { userId, onEvent } = params;

  const [listening, setListening] = useState<boolean>(false);

  const start = useCallback(() => {
    setListening(true);
  }, []);

  const stop = useCallback(() => {
    setListening(false);
  }, []);

  const renderWire = () => {
    const hasUser = userId > 0;
    const shouldRender = listening && hasUser;

    if (!shouldRender) {
      return null;
    }

    return <MonsterUpdatesWire userId={userId} onEvent={onEvent} />;
  };

  return { listening, start, stop, renderWire };
};

export default useMonsterUpdates;
