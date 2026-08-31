import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import FactionLoyaltyDashboard from './faction-loyalty-monitoring/components/faction-loyalty-dashboard';
import { EchoHandlerProvider } from '../../websocket-handler/components/echo-handler-provider';

const el = document.getElementById('faction-loyalty-monitoring');

if (el) {
  createRoot(el).render(
    <ServiceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <EchoHandlerProvider>
            <FactionLoyaltyDashboard />
          </EchoHandlerProvider>
        </ApiHandlerProvider>
      </EventSystemProvider>
    </ServiceContainer>
  );
}
