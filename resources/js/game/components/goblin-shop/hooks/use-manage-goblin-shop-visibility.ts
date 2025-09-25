import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageGoblinShopVisibilityDefinition from './definitions/use-manage-goblin-shop-visibility-definition';
import { GoblinShopActionEvents } from '../event-types/goblin-shop-action-events';

export const useManageGoblinShopVisibility =
  (): UseManageGoblinShopVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showGoblinShop, setShowGoblinShop] = useState<boolean>(false);

    const shopSectionVisibility = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(GoblinShopActionEvents.OPEN_GOBLIN_SHOP);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowGoblinShop(visible);
      };

      shopSectionVisibility.on(
        GoblinShopActionEvents.OPEN_GOBLIN_SHOP,
        updateVisibility
      );

      return () => {
        shopSectionVisibility.off(
          GoblinShopActionEvents.OPEN_GOBLIN_SHOP,
          updateVisibility
        );
      };
    }, [shopSectionVisibility]);

    const openGoblinShop = () => {
      shopSectionVisibility.emit(GoblinShopActionEvents.OPEN_GOBLIN_SHOP, true);
    };

    const closeGoblinShop = () => {
      shopSectionVisibility.emit(
        GoblinShopActionEvents.OPEN_GOBLIN_SHOP,
        false
      );
    };

    return { showGoblinShop, openGoblinShop, closeGoblinShop };
  };
