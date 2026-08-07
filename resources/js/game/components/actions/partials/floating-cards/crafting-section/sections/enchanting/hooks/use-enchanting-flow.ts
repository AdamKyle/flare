import { useMemo, useState } from 'react';

import UseEnchantingFlowDefinition from './definitions/use-enchanting-flow-definition';
import EnchantingApiResponseDefinition from '../api/definitions/enchanting-api-response-definition';
import CraftingItemPreviewDefinition from '../../../shared/api/definitions/crafting-item-preview-definition';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import { useEnchantItemApi } from '../api/hooks/use-enchant-item-api';
import { useEnchantingAffixesApi } from '../api/hooks/use-enchanting-affixes-api';
import { useEnchantingApi } from '../api/hooks/use-enchanting-api';
import { useEnchantingItemsApi } from '../api/hooks/use-enchanting-items-api';
import { EnchantingItemSource } from '../enums/enchanting-item-source';
import { buildDecoratedItemName } from '../utils/build-decorated-item-name';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useEnchantingFlow = (): UseEnchantingFlowDefinition => {
  const { gameData } = useGameData();

  const character = gameData?.character;
  const characterId = character?.id ?? 0;

  const { data, loading, error, replaceData } = useEnchantingApi({
    characterId,
  });

  const {
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    beginCraftingAction,
    completeCraftingRequest,
  } = useCraftingTimeout(character);

  const [selectedSource, setSelectedSource] =
    useState<EnchantingItemSource | null>(null);
  const [selectedSlotId, setSelectedSlotId] = useState<number | null>(null);
  const [selectedItemName, setSelectedItemName] = useState<string | null>(
    null
  );
  const [allItemsEnchanted, setAllItemsEnchanted] = useState<boolean>(false);
  const [prefixId, setPrefixId] = useState<number | null>(null);
  const [suffixId, setSuffixId] = useState<number | null>(null);
  const [lastEnchantSucceeded, setLastEnchantSucceeded] = useState<
    boolean | null
  >(null);
  const [resultPreview, setResultPreview] =
    useState<CraftingItemPreviewDefinition | null>(null);

  const hasEventChoice =
    data?.affixes.show_enchanting_for_event === true &&
    data.affixes.items_for_event.length > 0;

  const effectiveSource =
    selectedSource ?? (hasEventChoice ? null : EnchantingItemSource.REGULAR);

  const itemsApi = useEnchantingItemsApi({
    character_id: characterId,
    source: effectiveSource,
  });

  const prefixApi = useEnchantingAffixesApi({
    character_id: characterId,
    type: 'prefix',
  });

  const suffixApi = useEnchantingAffixesApi({
    character_id: characterId,
    type: 'suffix',
  });

  const effectiveSlotId = selectedSlotId;

  const affixIds = useMemo(
    () => [prefixId, suffixId].filter((id): id is number => id !== null),
    [prefixId, suffixId]
  );

  const canSubmit =
    effectiveSlotId !== null && effectiveSource !== null && affixIds.length > 0;

  const totalCost = useMemo(() => {
    const selectedPrefix = prefixApi.loadedAffixes.find(
      (affix) => affix.id === prefixId
    );
    const selectedSuffix = suffixApi.loadedAffixes.find(
      (affix) => affix.id === suffixId
    );

    return (selectedPrefix?.cost ?? 0) + (selectedSuffix?.cost ?? 0);
  }, [prefixApi.loadedAffixes, suffixApi.loadedAffixes, prefixId, suffixId]);

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
    setSelectedSlotId(null);
    setSelectedItemName(null);
    setAllItemsEnchanted(false);
    setLastEnchantSucceeded(null);
    setResultPreview(null);
  };

  const selectSlot = (nextSlotId: number, nextItemName: string): void => {
    setSelectedSlotId(nextSlotId);
    setSelectedItemName(nextItemName);
    setAllItemsEnchanted(false);
    setLastEnchantSucceeded(null);
    setResultPreview(null);
  };

  const selectPrefix = (nextPrefixId: number | null): void => {
    setPrefixId(nextPrefixId);
    setLastEnchantSucceeded(null);
    setResultPreview(null);
  };

  const selectSuffix = (nextSuffixId: number | null): void => {
    setSuffixId(nextSuffixId);
    setLastEnchantSucceeded(null);
    setResultPreview(null);
  };

  const advanceToNextEligibleItem = (
    response: EnchantingApiResponseDefinition
  ): void => {
    if (effectiveSource === EnchantingItemSource.EVENT) {
      const nextEventItem = response.affixes.items_for_event.find(
        (item) => item.affix_count === 0
      );

      if (nextEventItem) {
        setSelectedSlotId(nextEventItem.slot_id);
        setSelectedItemName(nextEventItem.item_name);
        setAllItemsEnchanted(false);

        return;
      }
    } else {
      const nextRegularItem = response.affixes.character_inventory.find(
        (item) => item.item.affix_count === 0
      );

      if (nextRegularItem) {
        setSelectedSlotId(nextRegularItem.id);
        setSelectedItemName(nextRegularItem.item.affix_name);
        setAllItemsEnchanted(false);

        return;
      }
    }

    setAllItemsEnchanted(true);

    if (response.result_preview) {
      setSelectedItemName(buildDecoratedItemName(response.result_preview));
    }
  };

  const submitEnchant = async (): Promise<void> => {
    setLastEnchantSucceeded(null);
    setResultPreview(null);

    if (!beginCraftingAction()) {
      return;
    }

    const response = await enchant();

    completeCraftingRequest();

    if (!response) {
      return;
    }

    replaceData({
      ...response,
      inventory_count: response.inventory_count ?? data?.inventory_count,
    });
    setLastEnchantSucceeded(response.enchant_succeeded ?? null);
    setResultPreview(response.result_preview ?? null);
    itemsApi.refreshItems();

    if (!response.enchant_succeeded) {
      setPrefixId(null);
      setSuffixId(null);

      return;
    }

    advanceToNextEligibleItem(response);
  };

  return {
    characterId,
    data,
    loading,
    error,
    mutationError,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    hasEventChoice,
    effectiveSource,
    effectiveSlotId,
    selectedItemName,
    allItemsEnchanted,
    selectedPrefixId: prefixId,
    selectedSuffixId: suffixId,
    totalCost,
    submitting,
    canSubmit,
    lastEnchantSucceeded,
    resultPreview,
    itemsApi,
    prefixApi,
    suffixApi,
    selectSource,
    selectSlot,
    selectPrefix,
    selectSuffix,
    submitEnchant,
  };
};
