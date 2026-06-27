import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import { AppScreenProvider } from 'configuration/screen-manager/screen-manager-kit';
import { EventSystemProvider } from 'event-system/components/event-system-provider';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import LogsDashboard from './logs-dashboard/components/logs-dashboard';
import BaseSidePeek from '../game/components/side-peeks/base/base-side-peek';

const el = document.getElementById('logs-dashboard');

if (el) {
  createRoot(el).render(
    <ServiceContainer>
      <EventSystemProvider>
        <ApiHandlerProvider>
          <BaseSidePeek />
          <AppScreenProvider>
            <LogsDashboard />
          </AppScreenProvider>
        </ApiHandlerProvider>
      </EventSystemProvider>
    </ServiceContainer>
  );
}
