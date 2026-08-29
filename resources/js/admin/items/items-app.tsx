import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { ItemScreens } from './screen-manager/item-screen-constants';
import {
  ItemScreenHost,
  ItemScreenProvider,
  useItemScreenNavigation,
} from './screen-manager/item-screen-kit';

import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const ItemListInitializer = (): null => {
  const navigation = useItemScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(ItemScreens.LIST, {});
  }, []);

  return null;
};

const ItemsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <ItemScreenProvider>
          <ItemListInitializer />
          <ItemScreenHost />
        </ItemScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const itemsElement = document.getElementById('items-admin-app');

if (itemsElement) {
  createRoot(itemsElement).render(<ItemsAdminApp />);
}
