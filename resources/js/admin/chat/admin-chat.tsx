import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { ServiceContainer } from 'service-container-provider/service-container';

import AdminChatSection from './admin-chat-section';
import { EchoHandlerProvider } from '../../websocket-handler/components/echo-handler-provider';

const administratorChatElement = document.getElementById('administrator-chat');

if (administratorChatElement) {
  createRoot(administratorChatElement).render(
    <ServiceContainer>
      <ApiHandlerProvider>
        <EchoHandlerProvider>
          <AdminChatSection />
        </EchoHandlerProvider>
      </ApiHandlerProvider>
    </ServiceContainer>
  );
}
