import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { ClassScreens } from './screen-manager/class-screen-constants';
import {
  ClassScreenHost,
  ClassScreenProvider,
  useClassScreenNavigation,
} from './screen-manager/class-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const ClassListInitializer = (): null => {
  const navigation = useClassScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(ClassScreens.LIST, {});
  }, []);

  return null;
};

const ClassesAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <ClassScreenProvider>
          <ClassListInitializer />
          <ClassScreenHost />
        </ClassScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const classesElement = document.getElementById('classes-admin-app');

if (classesElement) {
  createRoot(classesElement).render(<ClassesAdminApp />);
}
