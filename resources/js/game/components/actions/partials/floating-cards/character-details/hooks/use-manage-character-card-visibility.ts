import { useEffect, useState } from 'react';

import UseManageCharacterCardVisibilityDeffinition from './deffinitions/use-manage-character-card-visibility-deffinition';
import UseManageCharacterCardVisibilityState from './types/use-manage-character-card-visibility-state';
import EventSystemDefinition from '../../../../../../../event-system/deffintions/event-system-definition';
import { ActionCardEvents } from '../../EventTypes/action-cards';

export const useManageCharacterCardVisibility = (
  eventSystem: EventSystemDefinition
): UseManageCharacterCardVisibilityDeffinition => {
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
    useState<UseManageCharacterCardVisibilityState['showCharacterCard']>(false);

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
