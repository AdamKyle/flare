import React, { useCallback, useState } from 'react';

import UseExplorationOutputUpdatesDefinition from './definitions/use-exploration-output-updates-definition';
import UseExplorationOutputUpdatesParamsDefinition from './definitions/use-exploration-output-updates-params-definition';

import ExplorationOutputUpdatesWire from 'game-data/components/exploration-output-updates-wire';

export const useExplorationOutputUpdates = (
  params: UseExplorationOutputUpdatesParamsDefinition
): UseExplorationOutputUpdatesDefinition => {
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

    return <ExplorationOutputUpdatesWire userId={userId} onEvent={onEvent} />;
  };

  return { listening, start, renderWire };
};
