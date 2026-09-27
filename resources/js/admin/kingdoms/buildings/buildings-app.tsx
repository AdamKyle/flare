import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { BuildingScreens } from './screen-manager/building-screen-constants';
import {
  BuildingScreenHost,
  BuildingScreenProvider,
  useBuildingScreenNavigation,
} from './screen-manager/building-screen-kit';
import BaseSidePeek from '../../../game/components/side-peeks/base/base-side-peek';

const BuildingListInitializer = (): null => {
  const navigation = useBuildingScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(BuildingScreens.BUILDING_LIST, {});
  }, []);

  return null;
};

const KingdomBuildingsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <BuildingScreenProvider>
          <BuildingListInitializer />
          <BuildingScreenHost />
        </BuildingScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const kingdomBuildingsElement = document.getElementById(
  'kingdom-buildings-admin-app'
);

if (kingdomBuildingsElement) {
  createRoot(kingdomBuildingsElement).render(<KingdomBuildingsAdminApp />);
}
