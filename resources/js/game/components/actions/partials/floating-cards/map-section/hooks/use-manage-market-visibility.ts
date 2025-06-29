import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageMarketVisibilityDefinition from './definitions/use-manage-market-visibility-definition';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManageMarketVisibility =
  (): UseManageMarketVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const openMarketEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(ActionCardEvents.OPEN_MARKET);

    const [showMarket, setShowMarket] = useState<boolean>(false);

    useEffect(() => {
      const openMarketListener = (visible: boolean) => setShowMarket(visible);

      openMarketEmitter.on(ActionCardEvents.OPEN_MARKET, openMarketListener);

      return () => {
        openMarketEmitter.off(ActionCardEvents.OPEN_MARKET, openMarketListener);
      };
    }, [openMarketEmitter]);

    const openMarket = () => {
      openMarketEmitter.emit(ActionCardEvents.OPEN_MARKET, true);
    };

    const closeMarket = () => {
      openMarketEmitter.emit(ActionCardEvents.OPEN_MARKET, false);
    };

    return {
      showMarket,
      openMarket,
      closeMarket,
    };
  };
