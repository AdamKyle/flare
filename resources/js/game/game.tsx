import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode } from 'react';
import { SeerviceContainer } from 'service-container-provider/service-container';

import { GameCard } from './components/game-card';
import { ApiHandlerProvider } from '../axios/components/api-handler-provider';

export const Game = (): ReactNode => {
  return (
    <SeerviceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <GameCard />
        </ApiHandlerProvider>
      </EventSystemProvider>
    </SeerviceContainer>
  );
};
