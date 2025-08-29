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

    const [showShopCard, setShowShopCard] = useState<boolean>(false);

    useEffect(() => {
      const openShopListener = (visible: boolean) => setShowShopCard(visible);

      openShopEmitter.on(ActionCardEvents.OPEN_SHOP, openShopListener);

      return () => {
        openShopEmitter.off(ActionCardEvents.OPEN_SHOP, openShopListener);
      };
    }, [openShopEmitter]);

    const openShop = () => {
      openShopEmitter.emit(ActionCardEvents.OPEN_SHOP, true);

      const closeCharacterCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CHARACTER_CARD);

      const closeCraftingCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CRATING_CARD);

      const closeMapCard = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_MAP_SECTION);

      closeCraftingCardEvent.emit(ActionCardEvents.OPEN_CRATING_CARD, false);

      closeCharacterCardEvent.emit(ActionCardEvents.OPEN_CHARACTER_CARD, false);

      closeMapCard.emit(ActionCardEvents.OPEN_MAP_SECTION, false);
    };

    const closeShop = () => {
      openShopEmitter.emit(ActionCardEvents.OPEN_SHOP, false);
    };

    return {
      showShopCard,
      openShop,
      closeShop,
    };
  };
