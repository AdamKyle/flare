import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageCharacterCardVisibilityDefinition from './definitions/use-manage-character-card-visibility-definition';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManageCharacterCardVisibility =
  (): UseManageCharacterCardVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const manageCardEventEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(ActionCardEvents.OPEN_CHARACTER_CARD);

    const [showCharacterCard, setShowCharacterCard] = useState<boolean>(false);

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

      const closeMapCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_MAP_SECTION);

      const closeShopCard = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_SHOP);

      closeCraftingCardEvent.emit(ActionCardEvents.OPEN_CRATING_CARD, false);

      closeMapCardEvent.emit(ActionCardEvents.OPEN_MAP_SECTION, false);

      closeShopCard.emit(ActionCardEvents.OPEN_SHOP, false);

      manageCardEventEmitter.emit(ActionCardEvents.OPEN_CHARACTER_CARD, true);
    };

    const closeCharacterChard = () => {
      manageCardEventEmitter.emit(ActionCardEvents.OPEN_CHARACTER_CARD, false);
    };

    return { showCharacterCard, closeCharacterChard, openCharacterCard };
  };
