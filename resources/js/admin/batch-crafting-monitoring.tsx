import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import { EchoHandlerProvider } from '../websocket-handler/components/echo-handler-provider';
import BatchCraftingDashboard from './batch-crafting-monitoring/components/batch-crafting-dashboard';

const element = document.getElementById('batch-crafting-monitoring');

if (element) {
  createRoot(element).render(
    <ServiceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <EchoHandlerProvider>
            <BatchCraftingDashboard />
          </EchoHandlerProvider>
        </ApiHandlerProvider>
      </EventSystemProvider>
    </ServiceContainer>
  );
}
