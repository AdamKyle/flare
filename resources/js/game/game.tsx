import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { AppScreenProvider } from 'configuration/screen-manager/screen-manager-kit';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React from 'react';
import { ServiceContainer } from 'service-container-provider/service-container';

import { EchoHandlerProvider } from '../websocket-handler/components/echo-handler-provider';
import GuideQuestProvider from './components/guide-quests/components/guide-quest-provider';
import GuideWalkthroughProvider from './components/guide-quests/walkthrough/components/guide-walkthrough-provider';
import BaseSidePeek from './components/side-peeks/base/base-side-peek';
import GameSection from './game-section';

import GameDataProvider from 'game-data/components/game-data-provider';

export const Game = () => {
  return (
    <ServiceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <EchoHandlerProvider>
            <GameDataProvider>
              <GuideQuestProvider>
                <GuideWalkthroughProvider>
                  <BaseSidePeek />
                  <AppScreenProvider>
                    <GameSection />
                  </AppScreenProvider>
                </GuideWalkthroughProvider>
              </GuideQuestProvider>
            </GameDataProvider>
          </EchoHandlerProvider>
        </ApiHandlerProvider>
      </EventSystemProvider>
    </ServiceContainer>
  );
};
