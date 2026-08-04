import { useMemo, useRef, useState } from 'react';

import UseSeerAttachGemFlowDefinition from './definitions/use-seer-attach-gem-flow-definition';
import UseSeerAttachGemFlowParams from './definitions/use-seer-attach-gem-flow-params';
import AddGemToItemRequestDefinition from '../api/definitions/add-gem-to-item-request-definition';
import GemComparisonApiResponseDefinition from '../api/definitions/gem-comparison-api-response-definition';
import ReplaceGemOnItemRequestDefinition from '../api/definitions/replace-gem-on-item-request-definition';
import { SeerCampApiUrls } from '../api/enums/seer-camp-api-urls';
import { useGemComparisonApi } from '../api/hooks/use-gem-comparison-api';
import { useSeerActionApi } from '../api/hooks/use-seer-action-api';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const useSeerAttachGemFlow = ({
  characterId,
  items,
  gems,
  onSuccess,
}: UseSeerAttachGemFlowParams): UseSeerAttachGemFlowDefinition => {
  const {
    loading: comparisonLoading,
    error: comparisonError,
    compare,
  } = useGemComparisonApi({ characterId });

  const [slotId, setSlotId] = useState<number | null>(null);
  const [gemSlotId, setGemSlotId] = useState<number | null>(null);
  const [replaceId, setReplaceId] = useState<number | null>(null);
  const [comparison, setComparison] =
    useState<GemComparisonApiResponseDefinition | null>(null);

  const comparisonRequestIdRef = useRef(0);

  const itemOptions = useMemo<DropdownItem[]>(
    () =>
      items.map((item) => ({
        label: `${item.name} (${item.socket_amount} sockets)`,
        value: item.slot_id,
      })),
    [items]
  );

  const gemOptions = useMemo<DropdownItem[]>(
    () =>
      gems.map((gem) => ({
        label: `${gem.name} (Amount: ${gem.amount}, Tier: ${gem.tier})`,
        value: gem.slot_id,
      })),
    [gems]
  );

  const addRequest: AddGemToItemRequestDefinition | null =
    slotId !== null && gemSlotId !== null
      ? { slot_id: slotId, gem_slot_id: gemSlotId }
      : null;

  const replaceRequest: ReplaceGemOnItemRequestDefinition | null =
    slotId !== null && gemSlotId !== null && replaceId !== null
      ? {
          slot_id: slotId,
          gem_slot_id: gemSlotId,
          gem_slot_to_replace: replaceId,
        }
      : null;

  const addApi = useSeerActionApi({
    characterId,
    url: SeerCampApiUrls.ADD_GEM,
    request: addRequest,
  });

  const replaceApi = useSeerActionApi({
    characterId,
    url: SeerCampApiUrls.REPLACE_GEM,
    request: replaceRequest,
  });

  const requestComparison = (
    candidateSlotId: number | null,
    candidateGemSlotId: number | null
  ): void => {
    setReplaceId(null);
    setComparison(null);

    if (candidateSlotId === null || candidateGemSlotId === null) {
      return;
    }

    const requestId = ++comparisonRequestIdRef.current;

    void compare({
      slot_id: candidateSlotId,
      gem_slot_id: candidateGemSlotId,
    }).then((response) => {
      if (requestId === comparisonRequestIdRef.current) {
        setComparison(response);
      }
    });
  };

  const selectSlot = (nextSlotId: number): void => {
    setSlotId(nextSlotId);
    requestComparison(nextSlotId, gemSlotId);
  };

  const selectGemSlot = (nextGemSlotId: number): void => {
    setGemSlotId(nextGemSlotId);
    requestComparison(slotId, nextGemSlotId);
  };

  const selectReplaceGem = (nextReplaceId: number): void => {
    setReplaceId(nextReplaceId);
  };

  const resetSelections = (): void => {
    setSlotId(null);
    setGemSlotId(null);
    setReplaceId(null);
    setComparison(null);
  };

  const addGem = async (): Promise<void> => {
    const response = await addApi.submit();

    if (!response) {
      return;
    }

    onSuccess(response);
    resetSelections();
  };

  const replaceGem = async (): Promise<void> => {
    const response = await replaceApi.submit();

    if (!response) {
      return;
    }

    onSuccess(response);
    resetSelections();
  };

  return {
    slotId,
    gemSlotId,
    replaceId,
    comparison,
    comparisonLoading,
    error: comparisonError ?? addApi.error ?? replaceApi.error,
    addSubmitting: addApi.submitting,
    replaceSubmitting: replaceApi.submitting,
    canReplace: replaceRequest !== null,
    itemOptions,
    gemOptions,
    selectSlot,
    selectGemSlot,
    selectReplaceGem,
    addGem,
    replaceGem,
  };
};
