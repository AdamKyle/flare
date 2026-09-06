import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { RaceScreens } from './screen-manager/race-screen-constants';
import {
  RaceScreenHost,
  RaceScreenProvider,
  useRaceScreenNavigation,
} from './screen-manager/race-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const RaceListInitializer = (): null => {
  const navigation = useRaceScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(RaceScreens.LIST, {});
  }, []);

  return null;
};

const RacesAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <RaceScreenProvider>
          <RaceListInitializer />
          <RaceScreenHost />
        </RaceScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const racesElement = document.getElementById('races-admin-app');

if (racesElement) {
  createRoot(racesElement).render(<RacesAdminApp />);
}
