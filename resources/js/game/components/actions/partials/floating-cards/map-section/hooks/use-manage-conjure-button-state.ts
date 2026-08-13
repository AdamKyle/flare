import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { AllowConjureEventMap } from '../event-types/allow-conjure-event-map';
import { MapActions } from '../event-types/map-actions';
import UseManageConjureButtonStateDefinition from './definitions/use-manage-conjure-button-state-definition';

export const useManageConjureButtonState =
  (): UseManageConjureButtonStateDefinition => {
    const eventSystem = useEventSystem();

    const [isConjureEnabled, setIsConjureEnabled] = useState(false);

    const manageConjureButtonStateEmitter =
      eventSystem.fetchOrCreateEventEmitter<AllowConjureEventMap>(
        MapActions.ALLOW_CONJURE
      );

    useEffect(() => {
      const manageButtonState = (isEnabled: boolean) => {
        setIsConjureEnabled(isEnabled);
      };

      manageConjureButtonStateEmitter.on(
        MapActions.ALLOW_CONJURE,
        manageButtonState
      );

      return () => {
        manageConjureButtonStateEmitter.off(
          MapActions.ALLOW_CONJURE,
          manageButtonState
        );
      };
    }, [manageConjureButtonStateEmitter]);

    const manageConjureButtonState = (enabled: boolean) => {
      manageConjureButtonStateEmitter.emit(MapActions.ALLOW_CONJURE, enabled);
    };

    return {
      isConjureEnabled,
      manageConjureButtonState,
    };
  };
