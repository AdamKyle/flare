import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { ClassMasteryScreens } from './screen-manager/class-mastery-screen-constants';
import {
  ClassMasteryScreenHost,
  ClassMasteryScreenProvider,
  useClassMasteryScreenNavigation,
} from './screen-manager/class-mastery-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const ClassMasteryListInitializer = (): null => {
  const navigation = useClassMasteryScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(ClassMasteryScreens.LIST, {});
  }, []);

  return null;
};

const ClassMasteriesAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <ClassMasteryScreenProvider>
          <ClassMasteryListInitializer />
          <ClassMasteryScreenHost />
        </ClassMasteryScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const classMasteriesElement = document.getElementById(
  'class-masteries-admin-app'
);

if (classMasteriesElement) {
  createRoot(classMasteriesElement).render(<ClassMasteriesAdminApp />);
}
