import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode } from 'react';
import { SeerviceContainer } from 'service-container-provider/service-container';

import { GameCard } from './components/game-card';
import { EchoHandlerProvider } from '../websocket-handler/components/echo-handler-provider';
import BaseSidePeek from './components/side-peeks/base/base-side-peek';

import GameDataProvider from 'game-data/components/game-data-provider';

export const Game = (): ReactNode => {
  return (
    <SeerviceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <EchoHandlerProvider>
            <GameDataProvider>
              <BaseSidePeek />
              <GameCard />
            </GameDataProvider>
          </EchoHandlerProvider>
        </ApiHandlerProvider>
      </EventSystemProvider>
    </SeerviceContainer>
  );
};
