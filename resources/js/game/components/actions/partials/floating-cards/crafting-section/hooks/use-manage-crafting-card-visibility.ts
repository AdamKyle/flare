import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageCraftingCardVisibilityDefinition from './deffinitions/use-manage-crafting-card-visibility-definition';
import UseManageCraftingCardVisibilityState from './types/use-manage-crafting-card-visibility-state';
import { ActionCardEvents } from '../../EventTypes/action-cards';

export const useManageCraftingCardVisibility =
  (): UseManageCraftingCardVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const closeCardEventEmitter = eventSystem.isEventRegistered(
      ActionCardEvents.CLOSE_CRATING_CARD
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          ActionCardEvents.CLOSE_CRATING_CARD
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          ActionCardEvents.CLOSE_CRATING_CARD
        );

    const [showCraftingCard, setShowCraftingCard] =
      useState<UseManageCraftingCardVisibilityState['showCraftingCard']>(false);

    useEffect(() => {
      const closeCardListener = () => setShowCraftingCard(false);

      closeCardEventEmitter.on(
        ActionCardEvents.CLOSE_CRATING_CARD,
        closeCardListener
      );

      return () => {
        closeCardEventEmitter.off(
          ActionCardEvents.CLOSE_CRATING_CARD,
          closeCardListener
        );
      };
    }, [closeCardEventEmitter]);

    const openCraftingCard = () => {
      const closeCharacterCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.CLOSE_CHARACTER_CARD);
      const closeChatCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.CLOSE_CHAT_CARD);

      closeCharacterCardEvent.emit(ActionCardEvents.CLOSE_CHARACTER_CARD, true);
      closeChatCardEvent.emit(ActionCardEvents.CLOSE_CHAT_CARD, true);

      setShowCraftingCard(true);
    };

    return { showCraftingCard, openCraftingCard };
  };
