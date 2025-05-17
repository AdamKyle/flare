import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { MapActions } from '../event-types/map-actions';
import UseManageMapMovementErrorStateDefinition from './definitions/use-manage-map-movement-error-state-definition';

export const useManageMapMovementErrorState =
  (): UseManageMapMovementErrorStateDefinition => {
    const eventSystem = useEventSystem();

    const [errorMessage, setErrorMessage] = useState('');

    const manageMapErrorEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: string;
    }>(MapActions.SHOW_MAP_ERROR);

    useEffect(() => {
      const manageErrorState = (errorMessage: string) => {
        setErrorMessage(errorMessage);
      };

      manageMapErrorEmitter.on(MapActions.SHOW_MAP_ERROR, manageErrorState);

      return () => {
        manageMapErrorEmitter.off(
          MapActions.SHOW_MAP_MOVEMENT_TIMER,
          manageErrorState
        );
      };
    }, [manageMapErrorEmitter]);

    const showMessage = (message: string) => {
      manageMapErrorEmitter.emit(MapActions.SHOW_MAP_ERROR, message);
    };

    const resetErrorMessage = () => {
      manageMapErrorEmitter.emit(MapActions.SHOW_MAP_ERROR, '');
    };

    return {
      errorMessage,
      showMessage,
      resetErrorMessage,
    };
  };
