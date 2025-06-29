import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManagePlayerKingdomVisibilityDefinition from './definitions/use-manage-player-kingdom-visibility-definition';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManagePlayerKingdomManagementVisibility =
  (): UseManagePlayerKingdomVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const openPlayerKingdomsEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(ActionCardEvents.OPEN_SHOP);

    const [showPlayerKingdoms, setShowPlayerKingdoms] =
      useState<boolean>(false);

    useEffect(() => {
      const openPlayKingdomsListener = (visible: boolean) =>
        setShowPlayerKingdoms(visible);

      openPlayerKingdomsEmitter.on(
        ActionCardEvents.OPEN_PLAYER_KINGDOMS,
        openPlayKingdomsListener
      );

      return () => {
        openPlayerKingdomsEmitter.off(
          ActionCardEvents.OPEN_PLAYER_KINGDOMS,
          openPlayKingdomsListener
        );
      };
    }, [openPlayerKingdomsEmitter]);

    const openPlayerKingdoms = () => {
      openPlayerKingdomsEmitter.emit(ActionCardEvents.OPEN_SHOP, true);
    };

    const closePlayerKingdoms = () => {
      openPlayerKingdomsEmitter.emit(ActionCardEvents.OPEN_SHOP, false);
    };

    return {
      showPlayerKingdoms,
      openPlayerKingdoms,
      closePlayerKingdoms,
    };
  };
