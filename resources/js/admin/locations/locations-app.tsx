import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { LocationScreens } from './screen-manager/location-screen-constants';
import {
  LocationScreenHost,
  LocationScreenProvider,
  useLocationScreenNavigation,
} from './screen-manager/location-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const LocationListInitializer = (): null => {
  const navigation = useLocationScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(LocationScreens.LIST, {});
  }, []);

  return null;
};

const LocationsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <LocationScreenProvider>
          <LocationListInitializer />
          <LocationScreenHost />
        </LocationScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const locationsElement = document.getElementById('locations-admin-app');

if (locationsElement) {
  createRoot(locationsElement).render(<LocationsAdminApp />);
}
