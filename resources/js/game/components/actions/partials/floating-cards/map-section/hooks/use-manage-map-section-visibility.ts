import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { ActionCardEvents } from '../../event-types/action-cards';
import UseManageMapSectionVisibilityDefinition from './definitions/use-manage-map-section-visibility-definition';

export const useManageMapSectionVisibility =
  (): UseManageMapSectionVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const openMapEventEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(ActionCardEvents.OPEN_MAP_SECTION);

    const [showMapCard, setShowMapCard] =
      useState<boolean>(false);

    useEffect(() => {
      const closeCardListener = (visible: boolean) =>
        setShowMapCard(visible);

      openMapEventEmitter.on(
        ActionCardEvents.OPEN_MAP_SECTION,
        closeCardListener
      );

      return () => {
        openMapEventEmitter.off(
          ActionCardEvents.OPEN_MAP_SECTION,
          closeCardListener
        );
      };
    }, [openMapEventEmitter]);

    const openMapCard = () => {
      const closeCharacterCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CHARACTER_CARD);

      const closeCraftingCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CRATING_CARD);

      closeCraftingCardEvent.emit(ActionCardEvents.OPEN_CRATING_CARD, false);

      closeCharacterCardEvent.emit(ActionCardEvents.OPEN_CHARACTER_CARD, false);

      openMapEventEmitter.emit(ActionCardEvents.OPEN_MAP_SECTION, true);
    };

    const closeMapCard = () => {
      openMapEventEmitter.emit(ActionCardEvents.OPEN_MAP_SECTION, false);
    };

    return { showMapCard, closeMapCard, openMapCard };
  };
