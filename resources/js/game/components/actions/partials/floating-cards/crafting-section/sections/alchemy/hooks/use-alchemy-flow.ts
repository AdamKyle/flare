import { useMemo, useState } from 'react';

import UseAlchemyFlowDefinition from './definitions/use-alchemy-flow-definition';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import AlchemyResultDefinition from '../api/definitions/alchemy-result-definition';
import { useAlchemyApi } from '../api/hooks/use-alchemy-api';
import { useAlchemyItemsApi } from '../api/hooks/use-alchemy-items-api';
import { useTransmuteItemApi } from '../api/hooks/use-transmute-item-api';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useAlchemyFlow = (): UseAlchemyFlowDefinition => {
  const { gameData } = useGameData();

  const character = gameData?.character;
  const characterId = character?.id ?? 0;

  const { data, loading, error, replaceData } = useAlchemyApi({ characterId });

  const itemsApi = useAlchemyItemsApi({ character_id: characterId });

  const {
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    beginCraftingAction,
    completeCraftingRequest,
  } = useCraftingTimeout(character);

  const [selectedItemId, setSelectedItemId] = useState<number | null>(null);
  const [alchemyResult, setAlchemyResult] =
    useState<AlchemyResultDefinition | null>(null);

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
    transmuting,
    error: mutationError,
    transmute,
  } = useTransmuteItemApi({
    characterId,
    itemId: selectedItem?.id ?? null,
  });

  const canTransmute =
    Boolean(selectedItem) &&
    !transmuting &&
    !isCraftingDisabled &&
    !isFactionLoyaltyAutomationRunning;

  const selectItem = (itemId: number): void => {
    setSelectedItemId(itemId);
    setAlchemyResult(null);
  };

  const transmuteItem = async (): Promise<void> => {
    setAlchemyResult(null);

    if (!beginCraftingAction()) {
      return;
    }

    const response = await transmute();

    completeCraftingRequest();

    if (!response) {
      return;
    }

    replaceData(response);
    setAlchemyResult(response.alchemy_result ?? null);
  };

  return {
    characterId,
    data,
    loading,
    error,
    mutationError,
    selectedItem,
    transmuting,
    canTransmute,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    itemsApi,
    alchemyResult,
    selectItem,
    transmuteItem,
  };
};
