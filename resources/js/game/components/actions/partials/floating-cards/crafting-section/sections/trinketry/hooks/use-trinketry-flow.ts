import { useMemo, useState } from 'react';

import UseTrinketryFlowDefinition from './definitions/use-trinketry-flow-definition';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import { useCraftTrinketApi } from '../api/hooks/use-craft-trinket-api';
import { useTrinketryApi } from '../api/hooks/use-trinketry-api';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useTrinketryFlow = (): UseTrinketryFlowDefinition => {
  const { gameData } = useGameData();

  const character = gameData?.character;
  const characterId = character?.id ?? 0;

  const { data, loading, error, replaceData } = useTrinketryApi({
    characterId,
  });

  const { isCraftingDisabled } = useCraftingTimeout(character);

  const [selectedItemId, setSelectedItemId] = useState<number | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const selectedItem = useMemo(
    () =>
      data
        ? (data.items.find((item) => item.id === selectedItemId) ??
          data.items[0] ??
          null)
        : null,
    [data, selectedItemId]
  );

  const isFactionLoyaltyAutomationRunning =
    character?.is_faction_loyalty_automation_running === true;

  const {
    crafting,
    error: mutationError,
    craft,
  } = useCraftTrinketApi({
    characterId,
    itemId: selectedItem?.id ?? null,
  });

  const canCraft =
    Boolean(selectedItem) &&
    !crafting &&
    !isCraftingDisabled &&
    !isFactionLoyaltyAutomationRunning;

  const selectItem = (itemId: number): void => {
    setSelectedItemId(itemId);
    setStatus(null);
  };

  const craftItem = async (): Promise<void> => {
    const response = await craft();

    if (!response) {
      return;
    }

    replaceData(response);
    setStatus(
      response.message ?? 'Your Trinket crafting request was completed.'
    );
  };

  return {
    data,
    loading,
    error,
    mutationError,
    status,
    selectedItem,
    crafting,
    canCraft,
    selectItem,
    craftItem,
  };
};
