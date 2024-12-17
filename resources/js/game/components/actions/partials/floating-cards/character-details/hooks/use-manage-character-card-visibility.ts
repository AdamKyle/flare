import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageCharacterCardVisibilityDeffinition from './definitions/use-manage-character-card-visibility-definition';
import UseManageCharacterCardVisibilityState from './types/use-manage-character-card-visibility-state';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManageCharacterCardVisibility =
  (): UseManageCharacterCardVisibilityDeffinition => {
    const eventSystem = useEventSystem();

    const closeCardEventEmitter = eventSystem.isEventRegistered(
      ActionCardEvents.CLOSE_CHARACTER_CARD
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          ActionCardEvents.CLOSE_CHARACTER_CARD
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          ActionCardEvents.CLOSE_CHARACTER_CARD
        );

    const [showCharacterCard, setShowCharacterCard] =
      useState<UseManageCharacterCardVisibilityState['showCharacterCard']>(
        false
      );

    useEffect(() => {
      const closeCardListener = () => setShowCharacterCard(false);

      closeCardEventEmitter.on(
        ActionCardEvents.CLOSE_CHARACTER_CARD,
        closeCardListener
      );

      return () => {
        closeCardEventEmitter.off(
          ActionCardEvents.CLOSE_CHARACTER_CARD,
          closeCardListener
        );
      };
    }, [closeCardEventEmitter]);

    const openCharacterCard = () => {
      const closeCraftingCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.CLOSE_CRATING_CARD);
      const closeChatCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.CLOSE_CHAT_CARD);

      closeCraftingCardEvent.emit(ActionCardEvents.CLOSE_CRATING_CARD, true);
      closeChatCardEvent.emit(ActionCardEvents.CLOSE_CHAT_CARD, true);

      setShowCharacterCard(true);
    };

    return { showCharacterCard, openCharacterCard };
  };
