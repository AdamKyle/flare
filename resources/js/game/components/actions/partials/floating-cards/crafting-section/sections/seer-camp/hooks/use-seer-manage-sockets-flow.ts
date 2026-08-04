import { useMemo, useState } from 'react';

import UseSeerManageSocketsFlowDefinition from './definitions/use-seer-manage-sockets-flow-definition';
import UseSeerManageSocketsFlowParams from './definitions/use-seer-manage-sockets-flow-params';
import { SeerCampApiUrls } from '../api/enums/seer-camp-api-urls';
import { useSeerActionApi } from '../api/hooks/use-seer-action-api';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const useSeerManageSocketsFlow = ({
  characterId,
  items,
  onSuccess,
}: UseSeerManageSocketsFlowParams): UseSeerManageSocketsFlowDefinition => {
  const [slotId, setSlotId] = useState<number | null>(null);

  const selectedItem = useMemo(
    () => items.find((item) => item.slot_id === slotId) ?? null,
    [items, slotId]
  );

  const options = useMemo<DropdownItem[]>(
    () => items.map((item) => ({ label: item.name, value: item.slot_id })),
    [items]
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
  };

  const submit = async (): Promise<void> => {
    const response = await submitAction();

    if (response) {
      onSuccess(response);
    }
  };

  return {
    selectedItem,
    options,
    submitting,
    error,
    canSubmit,
    selectItem,
    submit,
  };
};
