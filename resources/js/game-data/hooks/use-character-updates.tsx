import React, { useCallback, useState } from 'react';

import { CharacterUpdatesWire } from 'game-data/components/character-updates-wire';
import UseCharacterUpdateDefinition from 'game-data/hooks/definitions/use-character-update-definition';
import UseCharacterUpdateParamsDefinition from 'game-data/hooks/definitions/use-character-update-params-definition';

const UseCharacterUpdates = (
  params: UseCharacterUpdateParamsDefinition
): UseCharacterUpdateDefinition => {
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
