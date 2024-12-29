import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageCharacterCardVisibilityDefinition from './definitions/use-manage-character-card-visibility-definition';
import UseManageCharacterCardVisibilityState from './types/use-manage-character-card-visibility-state';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManageCharacterCardVisibility =
  (): UseManageCharacterCardVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const manageCardEventEmitter = eventSystem.isEventRegistered(
      ActionCardEvents.OPEN_CHARACTER_CARD
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          ActionCardEvents.OPEN_CHARACTER_CARD
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          ActionCardEvents.OPEN_CHARACTER_CARD
        );

    const [showCharacterCard, setShowCharacterCard] =
      useState<UseManageCharacterCardVisibilityState['showCharacterCard']>(
        false
      );

    useEffect(() => {
      const closeCardListener = (visible: boolean) =>
        setShowCharacterCard(visible);

      manageCardEventEmitter.on(
        ActionCardEvents.OPEN_CHARACTER_CARD,
        closeCardListener
      );

      return () => {
        manageCardEventEmitter.off(
          ActionCardEvents.OPEN_CHARACTER_CARD,
          closeCardListener
        );
      };
    }, [manageCardEventEmitter]);

    const openCharacterCard = () => {
      const closeCraftingCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CRATING_CARD);
      const closeChatCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CHAT_CARD);

      closeCraftingCardEvent.emit(ActionCardEvents.OPEN_CRATING_CARD, false);
      closeChatCardEvent.emit(ActionCardEvents.OPEN_CHAT_CARD, false);

      manageCardEventEmitter.emit(ActionCardEvents.OPEN_CHARACTER_CARD, true);
    };

    const closeCharacterChard = () => {
      manageCardEventEmitter.emit(ActionCardEvents.OPEN_CHARACTER_CARD, false);
    };

    return { showCharacterCard, closeCharacterChard, openCharacterCard };
  };
