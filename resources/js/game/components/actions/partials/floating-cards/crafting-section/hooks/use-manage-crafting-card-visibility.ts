import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageCraftingCardVisibilityDefinition from './definitions/use-manage-crafting-card-visibility-definition';
import UseManageCraftingCardVisibilityState from './types/use-manage-crafting-card-visibility-state';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManageCraftingCardVisibility =
  (): UseManageCraftingCardVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const openCardEventEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(ActionCardEvents.OPEN_CRATING_CARD);

    const [showCraftingCard, setShowCraftingCard] =
      useState<UseManageCraftingCardVisibilityState['showCraftingCard']>(false);

    useEffect(() => {
      const closeCardListener = (visible: boolean) =>
        setShowCraftingCard(visible);

      openCardEventEmitter.on(
        ActionCardEvents.OPEN_CRATING_CARD,
        closeCardListener
      );

      return () => {
        openCardEventEmitter.off(
          ActionCardEvents.OPEN_CRATING_CARD,
          closeCardListener
        );
      };
    }, [openCardEventEmitter]);

    const openCraftingCard = () => {
      const closeCharacterCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CHARACTER_CARD);
      const closeChatCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CHAT_CARD);

      closeCharacterCardEvent.emit(ActionCardEvents.OPEN_CHARACTER_CARD, false);
      closeChatCardEvent.emit(ActionCardEvents.OPEN_CHAT_CARD, false);

      openCardEventEmitter.emit(ActionCardEvents.OPEN_CRATING_CARD, true);
    };

    const closeCraftingCard = () => {
      openCardEventEmitter.emit(ActionCardEvents.OPEN_CRATING_CARD, false);
    };

    return { showCraftingCard, closeCraftingCard, openCraftingCard };
  };
