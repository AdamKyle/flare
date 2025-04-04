import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { CharacterInventory } from '../event-types/character-inventory';
import useCharacterBackpackVisibilityDefinition from './definition/use-character-backpack-visibility-definition';
import { UseCharacterBackpackVisibilityState } from './state/use-character-backpack-visibility-state';

export const useCharacterBackpackVisibility =
  (): useCharacterBackpackVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showBackpack, setShowBackpack] =
      useState<UseCharacterBackpackVisibilityState['showBackpack']>(false);

    const manageCharacterBackpackEmitter =
      eventSystem.fetchOrCreateEventEmitter<{ [key: string]: boolean }>(
        CharacterInventory.OPEN_BACKPACK
      );

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowBackpack(visible);
      };

      manageCharacterBackpackEmitter.on(
        CharacterInventory.OPEN_BACKPACK,
        updateVisibility
      );

      return () => {
        manageCharacterBackpackEmitter.off(
          CharacterInventory.OPEN_BACKPACK,
          updateVisibility
        );
      };
    }, [manageCharacterBackpackEmitter]);

    const closeBackpack = () => {
      manageCharacterBackpackEmitter.emit(
        CharacterInventory.OPEN_BACKPACK,
        false
      );
    };

    return {
      showBackpack,
      closeBackpack,
    };
  };
