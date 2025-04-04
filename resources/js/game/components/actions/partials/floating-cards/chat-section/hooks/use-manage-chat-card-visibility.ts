import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import useManageChatCardVisibilityDefinition from './deffinitions/use-manage-chat-card-visibility-definition';
import UseManageChatCardVisibilityState from './types/use-manage-chat-card-visibility-state';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManageChatCardVisibility =
  (): useManageChatCardVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const openCardEventEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(ActionCardEvents.OPEN_CHAT_CARD);

    const [showChatCard, setShowChatCard] =
      useState<UseManageChatCardVisibilityState['showChatCard']>(false);

    useEffect(() => {
      const closeCardListener = (visible: boolean) => setShowChatCard(visible);

      openCardEventEmitter.on(
        ActionCardEvents.OPEN_CHAT_CARD,
        closeCardListener
      );

      return () => {
        openCardEventEmitter.off(
          ActionCardEvents.OPEN_CHAT_CARD,
          closeCardListener
        );
      };
    }, [openCardEventEmitter]);

    const openChatCard = () => {
      const closeCharacterCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CHARACTER_CARD);
      const closeCraftingCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CRATING_CARD);

      closeCharacterCardEvent.emit(ActionCardEvents.OPEN_CHARACTER_CARD, false);
      closeCraftingCardEvent.emit(ActionCardEvents.OPEN_CRATING_CARD, false);

      openCardEventEmitter.emit(ActionCardEvents.OPEN_CHAT_CARD, true);
    };

    const closeChatCard = () => {
      openCardEventEmitter.emit(ActionCardEvents.OPEN_CHAT_CARD, false);
    };

    return { showChatCard, closeChatCard, openChatCard };
  };
