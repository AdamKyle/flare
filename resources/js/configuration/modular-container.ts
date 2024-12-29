import { eventServiceContainer } from 'event-system/event-service-container';

import { ModularContainerDefinition } from './deffinitions/modular-container-definition';
import { axiosServiceContainer } from '../axios/axios-service-container';

/**
 * Register service containers here.
 */
export const serviceContainers: ModularContainerDefinition[] = [
  eventServiceContainer,
  axiosServiceContainer,
];
