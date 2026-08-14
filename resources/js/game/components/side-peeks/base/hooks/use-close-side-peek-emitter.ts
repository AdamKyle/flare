import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useCallback } from 'react';

import { CloseSidePeekEventMap } from '../event-map/side-peek-event-map';
import { SidePeek } from '../event-types/side-peek';
import UseCloseSidePeekEmitterDefinition from './deffinitions/use-close-side-peek-emitter-definition';

export const useCloseSidePeekEmitter =
  (): UseCloseSidePeekEmitterDefinition => {
    const eventSystem = useEventSystem();

    const emitter =
      eventSystem.fetchOrCreateEventEmitter<CloseSidePeekEventMap>(
        SidePeek.CLOSE_SIDE_PEEK
      );

    const closeSidePeek = useCallback(() => {
      emitter.emit(SidePeek.CLOSE_SIDE_PEEK, true);
    }, [emitter]);

    return {
      closeSidePeek,
    };
  };
