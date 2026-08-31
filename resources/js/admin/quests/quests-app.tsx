import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { QuestScreens } from './screen-manager/quest-screen-constants';
import {
  QuestScreenHost,
  QuestScreenProvider,
  useQuestScreenNavigation,
} from './screen-manager/quest-screen-kit';
import BaseSidePeek from '../../game/components/side-peeks/base/base-side-peek';

const QuestListInitializer = (): null => {
  const navigation = useQuestScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    navigationRef.current.resetTo(QuestScreens.LIST, {});
  }, []);

  return null;
};

const QuestsAdminApp = (): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <QuestScreenProvider>
          <QuestListInitializer />
          <QuestScreenHost />
        </QuestScreenProvider>
        <BaseSidePeek />
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const questsElement = document.getElementById('quests-admin-app');

if (questsElement) {
  createRoot(questsElement).render(<QuestsAdminApp />);
}
