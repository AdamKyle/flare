import { useMemo, useState } from 'react';

import UseSeerRemoveGemsFlowDefinition from './definitions/use-seer-remove-gems-flow-definition';
import UseSeerRemoveGemsFlowParams from './definitions/use-seer-remove-gems-flow-params';
import { SeerCampApiUrls } from '../api/enums/seer-camp-api-urls';
import { useRemoveGemsApi } from '../api/hooks/use-remove-gems-api';
import { useSeerActionApi } from '../api/hooks/use-seer-action-api';
import { useSeerItemsWithGemsApi } from '../api/hooks/use-seer-items-with-gems-api';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const useSeerRemoveGemsFlow = ({
  removalData,
  characterId,
  onSuccess,
}: UseSeerRemoveGemsFlowParams): UseSeerRemoveGemsFlowDefinition => {
  const [slotId, setSlotId] = useState<number | null>(null);
  const [gemId, setGemId] = useState<number | null>(null);

  const itemsApi = useSeerItemsWithGemsApi({ character_id: characterId });

  const selectedItem =
    removalData?.items.find((item) => item.slot_id === slotId) ?? null;

  const selectedDetails =
    removalData?.gems.find((detail) => detail.slot_id === slotId) ?? null;

  const selectedChange =
    selectedDetails?.comparison.atonement_changes.find(
      (change) => change.gem_id_to_remove === gemId
    ) ?? null;

  const oneApi = useSeerActionApi({
    characterId,
    url: SeerCampApiUrls.REMOVE_GEM,
    request:
      slotId !== null && gemId !== null
        ? { slot_id: slotId, gem_id: gemId }
        : null,
  });

  const allApi = useRemoveGemsApi({ characterId, inventorySlotId: slotId });

  const gemOptions = useMemo<DropdownItem[]>(
    () =>
      (selectedDetails?.gems ?? []).map((gem) => ({
        label: gem.gem_name,
        value: gem.gem_id,
      })),
    [selectedDetails]
  );

  const selectItem = (nextSlotId: number): void => {
    setSlotId(nextSlotId);
    setGemId(null);
  };

  const clearItem = (): void => {
    setSlotId(null);
    setGemId(null);
  };

  const selectGem = (nextGemId: number): void => {
    setGemId(nextGemId);
  };

  const clearGem = (): void => {
    setGemId(null);
  };

  const removeOne = async (): Promise<void> => {
    const response = await oneApi.submit();

    if (response) {
      onSuccess(response);
    }
  };

  const removeAll = async (): Promise<void> => {
    const response = await allApi.removeAll();

    if (response) {
      onSuccess(response);
    }
  };

  return {
    slotId,
    gemId,
    selectedItem,
    selectedDetails,
    selectedChange,
    itemsApi,
    gemOptions,
    isRemovingOne: oneApi.submitting,
    isRemovingAll: allApi.submitting,
    isSubmitting: oneApi.submitting || allApi.submitting,
    error: oneApi.error ?? allApi.error,
    selectItem,
    clearItem,
    selectGem,
    clearGem,
    removeOne,
    removeAll,
  };
};
