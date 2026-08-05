import { useMemo, useState } from 'react';

import UseTrinketryFlowDefinition from './definitions/use-trinketry-flow-definition';
import CraftingItemPreviewDefinition from '../../../shared/api/definitions/crafting-item-preview-definition';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import { useCraftTrinketApi } from '../api/hooks/use-craft-trinket-api';
import { useTrinketryApi } from '../api/hooks/use-trinketry-api';
import { useTrinketryItemsApi } from '../api/hooks/use-trinketry-items-api';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useTrinketryFlow = (): UseTrinketryFlowDefinition => {
  const { gameData } = useGameData();

  const character = gameData?.character;
  const characterId = character?.id ?? 0;

  const { data, loading, error, replaceData } = useTrinketryApi({
    characterId,
  });

  const itemsApi = useTrinketryItemsApi({ character_id: characterId });

  const { isTimeoutActive, isCraftingDisabled, progress, formattedRemaining } =
    useCraftingTimeout(character);

  const [selectedItemId, setSelectedItemId] = useState<number | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [resultPreview, setResultPreview] =
    useState<CraftingItemPreviewDefinition | null>(null);

  const selectedItem = useMemo(
    () =>
      itemsApi.loadedItems.find((item) => item.id === selectedItemId) ??
      itemsApi.loadedItems[0] ??
      null,
    [itemsApi.loadedItems, selectedItemId]
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
    setResultPreview(null);
  };

  const craftItem = async (): Promise<void> => {
    setResultPreview(null);

    const response = await craft();

    if (!response) {
      return;
    }

    replaceData(response);
    setStatus(
      response.message ?? 'Your Trinket crafting request was completed.'
    );
    setResultPreview(response.result_preview ?? null);
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
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    itemsApi,
    resultPreview,
    selectItem,
    craftItem,
  };
};
