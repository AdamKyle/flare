import { useState } from 'react';

import UseGemCraftingFlowDefinition from './definitions/use-gem-crafting-flow-definition';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import { useCraftGemApi } from '../api/hooks/use-craft-gem-api';
import { useGemCraftingApi } from '../api/hooks/use-gem-crafting-api';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useGemCraftingFlow = (): UseGemCraftingFlowDefinition => {
  const { gameData } = useGameData();

  const character = gameData?.character;
  const characterId = character?.id ?? 0;

  const { data, loading, error, replaceData } = useGemCraftingApi({
    characterId,
  });

  const { isCraftingDisabled } = useCraftingTimeout(character);

  const {
    crafting,
    error: mutationError,
    craft,
  } = useCraftGemApi({ characterId });

  const [selectedTier, setSelectedTier] = useState<number | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const effectiveSelectedTier =
    data && selectedTier !== null && selectedTier <= data.tiers.length
      ? selectedTier
      : null;

  const selectedTierData =
    effectiveSelectedTier === null
      ? null
      : (data?.tiers[effectiveSelectedTier - 1] ?? null);

  const isFactionLoyaltyAutomationRunning =
    character?.is_faction_loyalty_automation_running === true;

  const canCraft =
    Boolean(selectedTierData) &&
    !crafting &&
    !isCraftingDisabled &&
    !isFactionLoyaltyAutomationRunning;

  const selectTier = (tier: number): void => {
    setSelectedTier(tier);
    setStatus(null);
  };

  const craftGem = async (): Promise<void> => {
    if (effectiveSelectedTier === null) {
      return;
    }

    const response = await craft(effectiveSelectedTier);

    if (!response) {
      return;
    }

    replaceData(response);
    setStatus(response.message ?? 'Your Gem crafting request was completed.');
  };

  return {
    data,
    loading,
    error,
    mutationError,
    status,
    selectedTier: effectiveSelectedTier,
    selectedTierData,
    crafting,
    canCraft,
    selectTier,
    craftGem,
  };
};
