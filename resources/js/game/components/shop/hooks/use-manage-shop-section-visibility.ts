import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { ShopActionEvents } from '../event-types/shop-action-events';
import UseManageShopSectionVisibilityDefinition from './definitions/use-manage-shop-section-visibility-definition';

export const useManageShopSectionVisibility =
  (): UseManageShopSectionVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showShopSection, setShowShopSection] = useState<boolean>(false);

    const shopSectionVisibility = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(ShopActionEvents.OPEN_SHOP);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowShopSection(visible);
      };

      shopSectionVisibility.on(ShopActionEvents.OPEN_SHOP, updateVisibility);

      return () => {
        shopSectionVisibility.off(ShopActionEvents.OPEN_SHOP, updateVisibility);
      };
    }, [shopSectionVisibility]);

    const openShopSection = () => {
      shopSectionVisibility.emit(ShopActionEvents.OPEN_SHOP, true);
    };

    const closeShopSection = () => {
      shopSectionVisibility.emit(ShopActionEvents.OPEN_SHOP, false);
    };

    return { showShopSection, openShopSection, closeShopSection };
  };
