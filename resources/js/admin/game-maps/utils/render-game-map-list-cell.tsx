import React, { ReactNode } from 'react';

import GameMapDefinition from '../api/definitions/game-map-definition';

export const renderGameMapListCell = (row: GameMapDefinition): ReactNode => (
  <span className="text-glacier-900 dark:text-glacier-100 font-medium">
    {row.name}
  </span>
);
