import { useMemo, useState } from 'react';

import UseEnchantingFlowDefinition from './definitions/use-enchanting-flow-definition';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import { useEnchantItemApi } from '../api/hooks/use-enchant-item-api';
import { useEnchantingApi } from '../api/hooks/use-enchanting-api';
import { EnchantingItemSource } from '../enums/enchanting-item-source';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useEnchantingFlow = (): UseEnchantingFlowDefinition => {
  const { gameData } = useGameData();

  const character = gameData?.character;
  const characterId = character?.id ?? 0;

  const { data, loading, error, replaceData } = useEnchantingApi({
    characterId,
  });

  const { isCraftingDisabled } = useCraftingTimeout(character);

  const [selectedSource, setSelectedSource] =
    useState<EnchantingItemSource | null>(null);
  const [selectedSlotId, setSelectedSlotId] = useState<number | null>(null);
  const [prefixId, setPrefixId] = useState<number | null>(null);
  const [suffixId, setSuffixId] = useState<number | null>(null);
  const [lastEnchantSucceeded, setLastEnchantSucceeded] = useState<
    boolean | null
  >(null);

  const hasEventChoice =
    data?.affixes.show_enchanting_for_event === true &&
    data.affixes.items_for_event.length > 0;

  const effectiveSource =
    selectedSource ?? (hasEventChoice ? null : EnchantingItemSource.REGULAR);

  const availableSlotIds = useMemo(() => {
    if (!data || !effectiveSource) {
      return [];
    }

    return effectiveSource === EnchantingItemSource.EVENT
      ? data.affixes.items_for_event.map((item) => item.slot_id)
      : data.affixes.character_inventory.map((item) => item.id);
  }, [data, effectiveSource]);

  const effectiveSlotId =
    selectedSlotId !== null && availableSlotIds.includes(selectedSlotId)
      ? selectedSlotId
      : (availableSlotIds[0] ?? null);

  const affixIds = useMemo(
    () => [prefixId, suffixId].filter((id): id is number => id !== null),
    [prefixId, suffixId]
  );

  const canSubmit =
    effectiveSlotId !== null && effectiveSource !== null && affixIds.length > 0;

  const totalCost = useMemo(
    () =>
      data?.affixes.affixes
        .filter((affix) => affixIds.includes(affix.id))
        .reduce((sum, affix) => sum + affix.cost, 0) ?? 0,
    [data, affixIds]
  );

  const {
    submitting,
    error: mutationError,
    enchant,
  } = useEnchantItemApi({
    characterId,
    slotId: effectiveSlotId,
    affixIds,
    source: effectiveSource,
  });

  const selectSource = (nextSource: EnchantingItemSource): void => {
    setSelectedSource(nextSource);
    setLastEnchantSucceeded(null);
  };

  const selectSlot = (nextSlotId: number): void => {
    setSelectedSlotId(nextSlotId);
    setLastEnchantSucceeded(null);
  };

  const selectPrefix = (nextPrefixId: number | null): void => {
    setPrefixId(nextPrefixId);
    setLastEnchantSucceeded(null);
  };

  const selectSuffix = (nextSuffixId: number | null): void => {
    setSuffixId(nextSuffixId);
    setLastEnchantSucceeded(null);
  };

  const submitEnchant = async (): Promise<void> => {
    const response = await enchant();

    if (!response) {
      return;
    }

    replaceData({
      ...response,
      inventory_count: response.inventory_count ?? data?.inventory_count,
    });
    setLastEnchantSucceeded(response.enchant_succeeded ?? null);
  };

  return {
    data,
    loading,
    error,
    mutationError,
    isCraftingDisabled,
    hasEventChoice,
    effectiveSource,
    effectiveSlotId,
    selectedPrefixId: prefixId,
    selectedSuffixId: suffixId,
    totalCost,
    submitting,
    canSubmit,
    lastEnchantSucceeded,
    selectSource,
    selectSlot,
    selectPrefix,
    selectSuffix,
    submitEnchant,
  };
};
