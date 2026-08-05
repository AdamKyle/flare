import { useMemo, useState } from 'react';

import UseSeerManageSocketsFlowDefinition from './definitions/use-seer-manage-sockets-flow-definition';
import UseSeerManageSocketsFlowParams from './definitions/use-seer-manage-sockets-flow-params';
import CraftingItemPreviewDefinition from '../../../shared/api/definitions/crafting-item-preview-definition';
import { SeerCampApiUrls } from '../api/enums/seer-camp-api-urls';
import { useSeerActionApi } from '../api/hooks/use-seer-action-api';
import { useSeerItemsApi } from '../api/hooks/use-seer-items-api';

export const useSeerManageSocketsFlow = ({
  characterId,
  onSuccess,
}: UseSeerManageSocketsFlowParams): UseSeerManageSocketsFlowDefinition => {
  const [slotId, setSlotId] = useState<number | null>(null);
  const [resultPreview, setResultPreview] =
    useState<CraftingItemPreviewDefinition | null>(null);

  const itemsApi = useSeerItemsApi({
    character_id: characterId,
    purpose: 'sockets',
  });

  const selectedItem = useMemo(
    () => itemsApi.loadedItems.find((item) => item.slot_id === slotId) ?? null,
    [itemsApi.loadedItems, slotId]
  );

  const {
    submitting,
    error,
    submit: submitAction,
  } = useSeerActionApi({
    characterId,
    url: SeerCampApiUrls.ADD_SOCKETS,
    request: slotId !== null ? { slot_id: slotId } : null,
  });

  const canSubmit = slotId !== null && !submitting;

  const selectItem = (nextSlotId: number): void => {
    setSlotId(nextSlotId);
    setResultPreview(null);
  };

  const submit = async (): Promise<void> => {
    setResultPreview(null);

    const response = await submitAction();

    if (response) {
      onSuccess(response);
      setResultPreview(response.result_preview ?? null);
    }
  };

  return {
    selectedItem,
    itemsApi,
    submitting,
    error,
    canSubmit,
    resultPreview,
    selectItem,
    submit,
  };
};
