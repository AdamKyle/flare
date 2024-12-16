import { eventServiceContainer } from 'event-system/event-service-container';

import { ModularContainerDeffintion } from './deffinitions/modular-container-deffintion';

/**
 * Register service containers here.
 */
export const serviceContainers: ModularContainerDeffintion[] = [
  eventServiceContainer,
];
