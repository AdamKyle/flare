import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageShopVisibilityDefinition from './definitions/use-manage-shop-visibility-definition';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManageShopVisibility =
  (): UseManageShopVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const openShopEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(ActionCardEvents.OPEN_SHOP);

    const [showShop, setShowShop] = useState<boolean>(false);

    useEffect(() => {
      const openShopListener = (visible: boolean) => setShowShop(visible);

      openShopEmitter.on(ActionCardEvents.OPEN_SHOP, openShopListener);

      return () => {
        openShopEmitter.off(ActionCardEvents.OPEN_SHOP, openShopListener);
      };
    }, [openShopEmitter]);

    const openShop = () => {
      openShopEmitter.emit(ActionCardEvents.OPEN_SHOP, true);
    };

    const closeShop = () => {
      openShopEmitter.emit(ActionCardEvents.OPEN_SHOP, false);
    };

    return {
      showShop,
      openShop,
      closeShop,
    };
  };
