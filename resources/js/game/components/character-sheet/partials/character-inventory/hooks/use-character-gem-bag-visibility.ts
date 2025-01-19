import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { CharacterInventory } from '../event-types/character-inventory';
import UseCharacterGemBagVisibilityDefinition from './definition/use-character-gem-bag-visibility-definition';
import UseCharacterGemBagVisibilityState from './state/use-character-gem-bag-visibility-state';

export const useCharacterGemBagVisibility =
  (): UseCharacterGemBagVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showGemBag, setShowGemBag] =
      useState<UseCharacterGemBagVisibilityState['showGemBag']>(false);

    const manageCharacterGemBagVisibility = eventSystem.isEventRegistered(
      CharacterInventory.OPEN_GEM_BAG
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          CharacterInventory.OPEN_GEM_BAG
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          CharacterInventory.OPEN_GEM_BAG
        );

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowGemBag(visible);
      };

      manageCharacterGemBagVisibility.on(
        CharacterInventory.OPEN_GEM_BAG,
        updateVisibility
      );

      return () => {
        manageCharacterGemBagVisibility.off(
          CharacterInventory.OPEN_GEM_BAG,
          updateVisibility
        );
      };
    }, [manageCharacterGemBagVisibility]);

    const closeGemBag = () => {
      manageCharacterGemBagVisibility.emit(
        CharacterInventory.OPEN_GEM_BAG,
        false
      );
    };

    return {
      showGemBag,
      closeGemBag,
    };
  };
