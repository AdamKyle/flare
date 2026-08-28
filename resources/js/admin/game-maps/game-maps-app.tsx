import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { GameMapScreens } from './screen-manager/game-map-screen-constants';
import {
  GameMapScreenHost,
  GameMapScreenProvider,
  useGameMapScreenNavigation,
} from './screen-manager/game-map-screen-kit';

import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const GameMapListInitializer = (): null => {
  const navigation = useGameMapScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(GameMapScreens.LIST, {});
  }, []);

  return null;
};

const GameMapsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <GameMapScreenProvider>
          <GameMapListInitializer />
          <GameMapScreenHost />
        </GameMapScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const gameMapsElement = document.getElementById('game-maps-admin-app');

if (gameMapsElement) {
  createRoot(gameMapsElement).render(<GameMapsAdminApp />);
}
