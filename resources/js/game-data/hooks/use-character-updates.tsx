import React, { useCallback, useState } from 'react';

import UseMonsterUpdateDefinition from './definitions/use-monster-update-definition';

import { CharacterUpdatesWire } from 'game-data/components/character-updates-wire';
import UseCharacterUpdateParamsDefinition from 'game-data/hooks/definitions/use-character-update-params-definition';

const UseCharacterUpdates = (
  params: UseCharacterUpdateParamsDefinition
): UseMonsterUpdateDefinition => {
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

    return <CharacterUpdatesWire userId={userId} onEvent={onEvent} />;
  };

  return { listening, start, renderWire };
};

export default UseCharacterUpdates;
