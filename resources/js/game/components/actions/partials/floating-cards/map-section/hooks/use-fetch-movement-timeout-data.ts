import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseFetchMovementTimeoutDataDefinition from './definitions/use-fetch-movement-timeout-data-definition';
import MapMovementEventTimeoutDefinition from '../../../../../map-section/websockets/event-data-definitions/map-movement-event-timeout-definition';
import { MapActions } from '../event-types/map-actions';

export const useFetchMovementTimeoutData =
  (): UseFetchMovementTimeoutDataDefinition => {
    const eventSystem = useEventSystem();

    const showMapTimerEventEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: [MapMovementEventTimeoutDefinition];
    }>(MapActions.SHOW_MAP_MOVEMENT_TIMER);

    const [showTimerBar, setShowTimerBar] = useState(false);
    const [canMove, setCanMove] = useState(true);
    const [lengthOfTime, setLengthOfTime] = useState(0);

    useEffect(() => {
      const updateMapTimer = (data: MapMovementEventTimeoutDefinition) => {
        setShowTimerBar(data.activateBar);
        setCanMove(data.canMove);
        setLengthOfTime(data.forLength);
      };

      showMapTimerEventEmitter.on(MapActions.MOVE_CHARACTER, updateMapTimer);

      return () => {
        showMapTimerEventEmitter.off(MapActions.MOVE_CHARACTER, updateMapTimer);
      };
    }, [showMapTimerEventEmitter]);

    const handleEventData = (data: MapMovementEventTimeoutDefinition) => {
      showMapTimerEventEmitter.emit(MapActions.MOVE_CHARACTER, data);
    };

    return {
      handleEventData,
      showTimerBar,
      canMove,
      lengthOfTime,
    };
  };
