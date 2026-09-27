import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { PassiveSkillScreens } from './screen-manager/passive-skill-screen-constants';
import {
  PassiveSkillScreenHost,
  PassiveSkillScreenProvider,
  usePassiveSkillScreenNavigation,
} from './screen-manager/passive-skill-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const PassiveSkillListInitializer = (): null => {
  const navigation = usePassiveSkillScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(PassiveSkillScreens.LIST, {});
  }, []);

  return null;
};

const PassiveSkillsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <PassiveSkillScreenProvider>
          <PassiveSkillListInitializer />
          <PassiveSkillScreenHost />
        </PassiveSkillScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const passiveSkillsElement = document.getElementById(
  'passive-skills-admin-app'
);

if (passiveSkillsElement) {
  createRoot(passiveSkillsElement).render(<PassiveSkillsAdminApp />);
}
