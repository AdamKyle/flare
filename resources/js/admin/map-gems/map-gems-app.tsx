import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { MapGemScreens } from './screen-manager/map-gem-screen-constants';
import {
  MapGemScreenHost,
  MapGemScreenProvider,
  useMapGemScreenNavigation,
} from './screen-manager/map-gem-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const MapGemListInitializer = (): null => {
  const navigation = useMapGemScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(MapGemScreens.LIST, {});
  }, []);

  return null;
};

const MapGemsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <MapGemScreenProvider>
          <MapGemListInitializer />
          <MapGemScreenHost />
        </MapGemScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const mapGemsElement = document.getElementById('map-gems-admin-app');

if (mapGemsElement) {
  createRoot(mapGemsElement).render(<MapGemsAdminApp />);
}
