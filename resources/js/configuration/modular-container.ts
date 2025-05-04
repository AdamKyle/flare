import { axiosServiceContainer } from 'api-handler/axios-service-container';
import { eventServiceContainer } from 'event-system/event-service-container';

import { ModularContainerDefinition } from './deffinitions/modular-container-definition';
import { echoServiceContainer } from '../websocket-handler/echo-service-container';

/**
 * Register service containers here.
 */
export const serviceContainers: ModularContainerDefinition[] = [
  eventServiceContainer,
  axiosServiceContainer,
  echoServiceContainer,
];
