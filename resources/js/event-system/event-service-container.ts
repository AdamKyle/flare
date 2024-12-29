import { ModularContainerDefinition } from 'configuration/deffinitions/modular-container-definition';

import EventSystemDefinition from './deffintions/event-system-definition';
import EventSystem from './event-system';
import CoreContainerDefinition from '../service-container/deffinitions/core-container-definition';

export const eventServiceContainer: ModularContainerDefinition = (
  container: CoreContainerDefinition
) => {
  container.registerSingleton<EventSystemDefinition>(
    'EventSystem',
    EventSystem
  );
};
