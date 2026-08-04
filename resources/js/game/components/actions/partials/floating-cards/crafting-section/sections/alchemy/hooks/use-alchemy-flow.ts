import { useMemo, useState } from 'react';

import UseAlchemyFlowDefinition from './definitions/use-alchemy-flow-definition';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import { useAlchemyApi } from '../api/hooks/use-alchemy-api';
import { useTransmuteItemApi } from '../api/hooks/use-transmute-item-api';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useAlchemyFlow = (): UseAlchemyFlowDefinition => {
  const { gameData } = useGameData();

  const character = gameData?.character;
  const characterId = character?.id ?? 0;

  const { data, loading, error, replaceData } = useAlchemyApi({ characterId });

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
    setStatus(null);
  };

  const transmuteItem = async (): Promise<void> => {
    const response = await transmute();

    if (!response) {
      return;
    }

    replaceData(response);
    setStatus(response.message ?? 'Your transmutation request was completed.');
  };

  return {
    data,
    loading,
    error,
    mutationError,
    status,
    selectedItem,
    transmuting,
    canTransmute,
    selectItem,
    transmuteItem,
  };
};
