import React, { ReactNode } from 'react';
import { SeerviceContainer } from 'service-container-provider/service-container';

import { GameCard } from './components/game-card';

export const Game = (): ReactNode => {
  return (
    <SeerviceContainer>
      <GameCard />
    </SeerviceContainer>
  );
};
