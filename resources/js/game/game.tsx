import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode } from 'react';
import { SeerviceContainer } from 'service-container-provider/service-container';

import { GameCard } from './components/game-card';

import GameDataProvider from 'game-data/components/game-data-provider';

export const Game = (): ReactNode => {
  return (
    <SeerviceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <GameDataProvider>
            <GameCard />
          </GameDataProvider>
        </ApiHandlerProvider>
      </EventSystemProvider>
    </SeerviceContainer>
  );
};
