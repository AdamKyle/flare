import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { LocationGemScreens } from './screen-manager/location-gem-screen-constants';
import {
  LocationGemScreenHost,
  LocationGemScreenProvider,
  useLocationGemScreenNavigation,
} from './screen-manager/location-gem-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const LocationGemListInitializer = (): null => {
  const navigation = useLocationGemScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(LocationGemScreens.LIST, {});
  }, []);

  return null;
};

const LocationGemsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <LocationGemScreenProvider>
          <LocationGemListInitializer />
          <LocationGemScreenHost />
        </LocationGemScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const locationGemsElement = document.getElementById('location-gems-admin-app');

if (locationGemsElement) {
  createRoot(locationGemsElement).render(<LocationGemsAdminApp />);
}
