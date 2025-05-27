import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { SidePeek } from '../event-types/side-peek';
import UseCloseSidePeekEmitterDefinition from './deffinitions/use-close-side-peek-emitter-definition';

export const useCloseSidePeekEmitter =
  (): UseCloseSidePeekEmitterDefinition => {
    const eventSystem = useEventSystem();

    const [shouldClose, setShouldClose] = useState(false);

    const emitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(SidePeek.CLOSE_SIDE_PEEK);

    useEffect(() => {
      const handleUpdateShouldCloseSidePeek = (shouldClose: boolean) => {
        setShouldClose(shouldClose);
      };

      emitter.on(SidePeek.CLOSE_SIDE_PEEK, handleUpdateShouldCloseSidePeek);

      return () => {
        emitter.off(SidePeek.CLOSE_SIDE_PEEK, handleUpdateShouldCloseSidePeek);
      };
    }, [emitter]);

    const closeSidePeek = () => {
      emitter.emit(SidePeek.CLOSE_SIDE_PEEK, true);
    };

    return {
      shouldClose,
      closeSidePeek,
    };
  };
