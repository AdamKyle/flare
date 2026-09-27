import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { UnitScreens } from './screen-manager/unit-screen-constants';
import {
  UnitScreenHost,
  UnitScreenProvider,
  useUnitScreenNavigation,
} from './screen-manager/unit-screen-kit';
import BaseSidePeek from '../../../game/components/side-peeks/base/base-side-peek';

const UnitListInitializer = (): null => {
  const navigation = useUnitScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(UnitScreens.UNIT_LIST, {});
  }, []);

  return null;
};

const KingdomUnitsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <UnitScreenProvider>
          <UnitListInitializer />
          <UnitScreenHost />
        </UnitScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const kingdomUnitsElement = document.getElementById('kingdom-units-admin-app');

if (kingdomUnitsElement) {
  createRoot(kingdomUnitsElement).render(<KingdomUnitsAdminApp />);
}
