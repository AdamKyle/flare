import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { Map } from '../event-types/map';
import UseToggleFullMapVisibilityDefinition from './definitions/use-toggle-full-map-visibility-definition';

export const useToggleFullMapVisibility =
  (): UseToggleFullMapVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showFullMap, setShowFullMap] = useState<boolean>(false);

    const manageFullMapVisibility = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(Map.FULL_SCREEN_MAP);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowFullMap(visible);
      };

      manageFullMapVisibility.on(Map.FULL_SCREEN_MAP, updateVisibility);

      return () => {
        manageFullMapVisibility.off(Map.FULL_SCREEN_MAP, updateVisibility);
      };
    }, [manageFullMapVisibility]);

    const openFullMap = () => {
      manageFullMapVisibility.emit(Map.FULL_SCREEN_MAP, true);
    };

    const closeMap = () => {
      setShowFullMap(false);
    };

    return {
      openFullMap,
      closeMap,
      showFullMap,
    };
  };
