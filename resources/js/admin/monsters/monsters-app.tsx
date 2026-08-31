import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { MonsterScreens } from './screen-manager/monster-screen-constants';
import {
  MonsterScreenHost,
  MonsterScreenProvider,
  useMonsterScreenNavigation,
} from './screen-manager/monster-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const MonsterListInitializer = (): null => {
  const navigation = useMonsterScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(MonsterScreens.LIST, {});
  }, []);

  return null;
};

const MonstersAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <MonsterScreenProvider>
          <MonsterListInitializer />
          <MonsterScreenHost />
        </MonsterScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const monstersElement = document.getElementById('monsters-admin-app');

if (monstersElement) {
  createRoot(monstersElement).render(<MonstersAdminApp />);
}
