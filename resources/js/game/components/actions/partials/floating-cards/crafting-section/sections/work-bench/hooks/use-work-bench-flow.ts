import { useState } from 'react';

import UseWorkBenchFlowDefinition from './definitions/use-work-bench-flow-definition';
import CraftingItemPreviewDefinition from '../../../shared/api/definitions/crafting-item-preview-definition';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import { useApplyHolyOilApi } from '../api/hooks/use-apply-holy-oil-api';
import { useHolyOilsApi } from '../api/hooks/use-holy-oils-api';
import { useWorkBenchApi } from '../api/hooks/use-work-bench-api';
import { useWorkBenchItemsApi } from '../api/hooks/use-work-bench-items-api';
import { buildApplyHolyOilRequest } from '../utils/build-apply-holy-oil-request';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useWorkBenchFlow = (): UseWorkBenchFlowDefinition => {
  const { gameData } = useGameData();

  const character = gameData?.character;
  const characterId = character?.id ?? 0;

  const { data, loading, error, replaceData } = useWorkBenchApi({
    characterId,
  });

  const itemsApi = useWorkBenchItemsApi({ character_id: characterId });
  const oilsApi = useHolyOilsApi({ character_id: characterId });

  const { isTimeoutActive, isCraftingDisabled, progress, formattedRemaining } =
    useCraftingTimeout(character);

  const [selectedTargetSlotId, setSelectedTargetSlotId] = useState<
    number | null
  >(null);
  const [selectedAlchemySlotId, setSelectedAlchemySlotId] = useState<
    number | null
  >(null);
  const [status, setStatus] = useState<string | null>(null);
  const [resultPreview, setResultPreview] =
    useState<CraftingItemPreviewDefinition | null>(null);

  const selectedTarget =
    itemsApi.loadedItems.find(
      (slot) => slot.slot_id === selectedTargetSlotId
    ) ?? null;

  const selectedOil =
    oilsApi.loadedItems.find((slot) => slot.id === selectedAlchemySlotId) ??
    null;

  const request = buildApplyHolyOilRequest(
    selectedTarget?.item_id ?? null,
    selectedOil?.id ?? null
  );

  const cost =
    selectedTarget && selectedOil
      ? (data?.costs[selectedTarget.slot_id]?.[selectedOil.id] ?? null)
      : null;

  const statBonusRange = selectedOil
    ? {
        min: selectedOil.possible_stat_bonus_minimum,
        max: selectedOil.possible_stat_bonus_maximum,
      }
    : null;

  const devoidanceRange = selectedOil
    ? {
        min: selectedOil.possible_devoidance_minimum,
        max: selectedOil.possible_devoidance_maximum,
      }
    : null;

  const {
    submitting,
    error: mutationError,
    apply,
  } = useApplyHolyOilApi({
    characterId,
    request,
  });

  const canSubmit = request !== null && !submitting && !isCraftingDisabled;

  const selectTarget = (slotId: number): void => {
    setSelectedTargetSlotId(slotId);
    setStatus(null);
    setResultPreview(null);
  };

  const selectOil = (slotId: number): void => {
    setSelectedAlchemySlotId(slotId);
    setStatus(null);
    setResultPreview(null);
  };

  const submitApply = async (): Promise<void> => {
    setResultPreview(null);

    const response = await apply();

    if (!response) {
      return;
    }

    replaceData(response);
    setStatus(response.message ?? 'The Holy Oil was applied.');
    setResultPreview(response.result_preview ?? null);
    setSelectedAlchemySlotId(null);
  };

  return {
    data,
    loading,
    error,
    mutationError,
    status,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    selectedTargetSlotId,
    selectedAlchemySlotId,
    selectedTarget,
    selectedOil,
    cost,
    statBonusRange,
    devoidanceRange,
    resultPreview,
    submitting,
    canSubmit,
    itemsApi,
    oilsApi,
    selectTarget,
    selectOil,
    submitApply,
  };
};
