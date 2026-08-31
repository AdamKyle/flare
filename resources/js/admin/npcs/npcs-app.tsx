import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { NpcScreens } from './screen-manager/npc-screen-constants';
import {
  NpcScreenHost,
  NpcScreenProvider,
  useNpcScreenNavigation,
} from './screen-manager/npc-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const NpcListInitializer = (): null => {
  const navigation = useNpcScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(NpcScreens.LIST, {});
  }, []);

  return null;
};

const NpcsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <NpcScreenProvider>
          <NpcListInitializer />
          <NpcScreenHost />
        </NpcScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const npcsElement = document.getElementById('npcs-admin-app');

if (npcsElement) {
  createRoot(npcsElement).render(<NpcsAdminApp />);
}
