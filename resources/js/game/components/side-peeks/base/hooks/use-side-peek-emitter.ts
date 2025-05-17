import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseSidePeekEmitterDefinition from './deffinitions/use-side-peek-emitter-definition';
import { SidePeekEventMap } from '../event-map/side-peek-event-map';
import { SidePeek } from '../event-types/side-peek';

export const useSidePeekEmitter = (): UseSidePeekEmitterDefinition => {
  const eventSystem = useEventSystem();

  return eventSystem.fetchOrCreateEventEmitter<SidePeekEventMap>(
    SidePeek.SIDE_PEEK
  );
};
