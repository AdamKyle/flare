import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import DelveDashboard from './delve-monitoring/components/delve-dashboard';
import { EchoHandlerProvider } from '../../websocket-handler/components/echo-handler-provider';

const el = document.getElementById('delve-monitoring');

if (el) {
  createRoot(el).render(
    <ServiceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <EchoHandlerProvider>
            <DelveDashboard />
          </EchoHandlerProvider>
        </ApiHandlerProvider>
      </EventSystemProvider>
    </ServiceContainer>
  );
}
