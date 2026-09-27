import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React, { ReactNode, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { GuideQuestScreens } from './screen-manager/guide-quest-screen-constants';
import {
  GuideQuestScreenHost,
  GuideQuestScreenProvider,
  useGuideQuestScreenNavigation,
} from './screen-manager/guide-quest-screen-kit';

const GuideQuestInitializer = ({
  guide_quest_id: guideQuestId,
}: {
  guide_quest_id: number | null;
}): null => {
  const navigation = useGuideQuestScreenNavigation();
  const navigationRef = useRef(navigation);
  navigationRef.current = navigation;

  useEffect(() => {
    if (guideQuestId === null) {
      navigationRef.current.resetTo(GuideQuestScreens.LIST, {});
      return;
    }

    navigationRef.current.resetTo(GuideQuestScreens.SHOW, {
      guide_quest_id: guideQuestId,
    });
  }, [guideQuestId]);

  return null;
};

const GuideQuestsAdminApp = ({
  guide_quest_id: guideQuestId,
}: {
  guide_quest_id: number | null;
}): ReactNode => (
  <ServiceContainer>
    <EventSystemProvider>
      <ApiHandlerProvider>
        <GuideQuestScreenProvider>
          <GuideQuestInitializer guide_quest_id={guideQuestId} />
          <GuideQuestScreenHost />
        </GuideQuestScreenProvider>
      </ApiHandlerProvider>
    </EventSystemProvider>
  </ServiceContainer>
);

const guideQuestsElement = document.getElementById('guide-quests-admin-app');

if (guideQuestsElement) {
  const rawGuideQuestId = guideQuestsElement.dataset.guideQuestId;
  const parsedGuideQuestId = rawGuideQuestId ? Number(rawGuideQuestId) : null;
  const guideQuestId =
    parsedGuideQuestId !== null &&
    Number.isInteger(parsedGuideQuestId) &&
    parsedGuideQuestId > 0
      ? parsedGuideQuestId
      : null;

  createRoot(guideQuestsElement).render(
    <GuideQuestsAdminApp guide_quest_id={guideQuestId} />
  );
}
