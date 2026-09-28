import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { GemAbilityScreens } from './screen-manager/gem-ability-screen-constants';
import {
  GemAbilityScreenHost,
  GemAbilityScreenProvider,
  useGemAbilityScreenNavigation,
} from './screen-manager/gem-ability-screen-kit';

const GemAbilityListInitializer = (): null => {
  const navigation = useGemAbilityScreenNavigation();
  const navigationRef = useRef(navigation);

  useEffect(() => {
    navigationRef.current = navigation;
  }, [navigation]);

  useEffect(() => {
    navigationRef.current.resetTo(GemAbilityScreens.LIST, {});
  }, []);

  return null;
};

const GemAbilitiesAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <GemAbilityScreenProvider>
          <GemAbilityListInitializer />
          <GemAbilityScreenHost />
        </GemAbilityScreenProvider>
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const gemAbilitiesElement = document.getElementById('gem-abilities-admin-app');

if (gemAbilitiesElement) {
  createRoot(gemAbilitiesElement).render(<GemAbilitiesAdminApp />);
}
