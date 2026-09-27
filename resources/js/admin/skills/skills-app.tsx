import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { SkillScreens } from './screen-manager/skill-screen-constants';
import {
  SkillScreenHost,
  SkillScreenProvider,
  useSkillScreenNavigation,
} from './screen-manager/skill-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const SkillListInitializer = (): null => {
  const navigation = useSkillScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(SkillScreens.LIST, {});
  }, []);

  return null;
};

const SkillsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <SkillScreenProvider>
          <SkillListInitializer />
          <SkillScreenHost />
        </SkillScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const skillsElement = document.getElementById('skills-admin-app');

if (skillsElement) {
  createRoot(skillsElement).render(<SkillsAdminApp />);
}
