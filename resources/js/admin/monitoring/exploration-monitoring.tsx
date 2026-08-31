import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import ExplorationDashboard from './exploration-monitoring/components/exploration-dashboard';
import { EchoHandlerProvider } from '../../websocket-handler/components/echo-handler-provider';

const element = document.getElementById('exploration-monitoring');

if (element) {
  createRoot(element).render(
    <ServiceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <EchoHandlerProvider>
            <ExplorationDashboard />
          </EchoHandlerProvider>
        </ApiHandlerProvider>
      </EventSystemProvider>
    </ServiceContainer>
  );
}
