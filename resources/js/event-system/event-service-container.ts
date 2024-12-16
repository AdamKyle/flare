import EventSystemDefinition from './deffintions/event-system-definition';
import EventSystem from './event-system';
import { ModularContainerDeffintion } from '../configuration/deffinitions/modular-container-deffintion';
import CoreContainerDefinition from '../service-container/deffinitions/core-container-definition';

export const eventServiceContainer: ModularContainerDeffintion = (
  container: CoreContainerDefinition
) => {
  container.registerSingleton<EventSystemDefinition>(
    'EventSystem',
    EventSystem
  );
};
