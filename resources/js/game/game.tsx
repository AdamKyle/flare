import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode } from 'react';
import { SeerviceContainer } from 'service-container-provider/service-container';

import { GameCard } from './components/game-card';

export const Game = (): ReactNode => {
  return (
    <SeerviceContainer>
      <EventSystemProvider>
        <GameCard />
      </EventSystemProvider>
    </SeerviceContainer>
  );
};
