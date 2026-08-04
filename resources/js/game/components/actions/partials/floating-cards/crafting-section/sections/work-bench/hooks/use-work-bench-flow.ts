import { useState } from 'react';

import UseWorkBenchFlowDefinition from './definitions/use-work-bench-flow-definition';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import { useApplyHolyOilApi } from '../api/hooks/use-apply-holy-oil-api';
import { useWorkBenchApi } from '../api/hooks/use-work-bench-api';
import { buildApplyHolyOilRequest } from '../utils/build-apply-holy-oil-request';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useWorkBenchFlow = (): UseWorkBenchFlowDefinition => {
  const { gameData } = useGameData();

  const character = gameData?.character;
  const characterId = character?.id ?? 0;

  const { data, loading, error, replaceData } = useWorkBenchApi({
    characterId,
  });

  const { isCraftingDisabled } = useCraftingTimeout(character);

  const [selectedTargetSlotId, setSelectedTargetSlotId] = useState<
    number | null
  >(null);
  const [selectedAlchemySlotId, setSelectedAlchemySlotId] = useState<
    number | null
  >(null);
  const [status, setStatus] = useState<string | null>(null);

  const selectedTarget =
    data?.items.find((slot) => slot.id === selectedTargetSlotId) ?? null;

  const selectedOil =
    data?.alchemy_items.find((slot) => slot.id === selectedAlchemySlotId) ??
    null;

  const request = buildApplyHolyOilRequest(
    selectedTarget?.item.id ?? null,
    selectedOil?.id ?? null
  );

  const cost =
    selectedTarget && selectedOil
      ? (data?.costs[selectedTarget.id]?.[selectedOil.id] ?? null)
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
  };

  const selectOil = (slotId: number): void => {
    setSelectedAlchemySlotId(slotId);
    setStatus(null);
  };

  const submitApply = async (): Promise<void> => {
    const response = await apply();

    if (!response) {
      return;
    }

    replaceData(response);
    setStatus(response.message ?? 'The Holy Oil was applied.');
    setSelectedAlchemySlotId(null);
  };

  return {
    data,
    loading,
    error,
    mutationError,
    status,
    isCraftingDisabled,
    selectedTargetSlotId,
    selectedAlchemySlotId,
    selectedTarget,
    selectedOil,
    cost,
    submitting,
    canSubmit,
    selectTarget,
    selectOil,
    submitApply,
  };
};
