import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { MapActions } from '../event-types/map-actions';
import UseManageSetSailButtonStateDefinition from './definitions/use-manage-set-sail-button-state-definition';

export const useManageSetSailButtonState =
  (): UseManageSetSailButtonStateDefinition => {
    const eventSystem = useEventSystem();

    const [isSetSailEnabled, setIsSetSailEnabled] = useState(false);

    const manageSetSailButtonStateEmitter =
      eventSystem.fetchOrCreateEventEmitter<{ [key: string]: boolean }>(
        MapActions.ALLOW_SET_SAIL
      );

    useEffect(() => {
      const manageButtonState = (isEnabled: boolean) => {
        setIsSetSailEnabled(isEnabled);
      };

      manageSetSailButtonStateEmitter.on(
        MapActions.ALLOW_SET_SAIL,
        manageButtonState
      );

      return () => {
        manageSetSailButtonStateEmitter.off(
          MapActions.ALLOW_SET_SAIL,
          manageButtonState
        );
      };
    }, [manageSetSailButtonStateEmitter]);

    const manageSetSailButtonState = (enabled: boolean) => {
      manageSetSailButtonStateEmitter.emit(MapActions.ALLOW_SET_SAIL, enabled);
    };

    return {
      isSetSailEnabled,
      manageSetSailButtonState,
    };
  };
