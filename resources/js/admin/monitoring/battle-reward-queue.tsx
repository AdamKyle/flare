import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import RewardQueueDashboard from './battle-reward-queue/components/reward-queue-dashboard';
import { EchoHandlerProvider } from '../../websocket-handler/components/echo-handler-provider';

const rewardQueueElement = document.getElementById('character-reward-queue');

if (rewardQueueElement) {
  createRoot(rewardQueueElement).render(
    <ServiceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <EchoHandlerProvider>
            <RewardQueueDashboard />
          </EchoHandlerProvider>
        </ApiHandlerProvider>
      </EventSystemProvider>
    </ServiceContainer>
  );
}
