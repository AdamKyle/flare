import { useMemo, useState } from 'react';

import UseQueenMoveAffixesFlowDefinition, {
  UseQueenMoveAffixesFlowParams,
} from './definitions/use-queen-move-affixes-flow-definition';
import QueenInventorySlotDefinition from '../api/definitions/queen-inventory-slot-definition';
import { useMoveQueenAffixesApi } from '../api/hooks/use-move-queen-affixes-api';
import { QueenAffixSelection } from '../enums/queen-affix-selection';
import { buildMoveQueenAffixesRequest } from '../utils/build-move-queen-affixes-request';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const buildAffixOptions = (
  selectedSource: QueenInventorySlotDefinition | null
): DropdownItem[] => {
  if (!selectedSource) {
    return [];
  }

  const options: DropdownItem[] = [];

  if (selectedSource.item.item_prefix) {
    options.push({ label: 'Prefix', value: QueenAffixSelection.PREFIX });
  }

  if (selectedSource.item.item_suffix) {
    options.push({ label: 'Suffix', value: QueenAffixSelection.SUFFIX });
  }

  if (options.length === 2) {
    options.push({
      label: 'Both',
      value: QueenAffixSelection.ALL_ENCHANTMENTS,
    });
  }

  return options;
};

export const useQueenMoveAffixesFlow = ({
  characterId,
  data,
  onDataReplaced,
  onSuccess,
}: UseQueenMoveAffixesFlowParams): UseQueenMoveAffixesFlowDefinition => {
  const [sourceId, setSourceId] = useState<number | null>(null);
  const [destinationId, setDestinationId] = useState<number | null>(null);
  const [affix, setAffix] = useState<QueenAffixSelection | null>(null);

  const uniqueSlots = data.unique_slots;
  const nonUniqueSlots = data.non_unique_slots;
  const hasSourceSlots = uniqueSlots.length > 0;

  const selectedSource = useMemo(
    () => uniqueSlots.find((slot) => slot.id === sourceId) ?? null,
    [uniqueSlots, sourceId]
  );

  const sourceOptions = useMemo<DropdownItem[]>(
    () =>
      uniqueSlots.map((slot) => ({
        label: slot.item.affix_name,
        value: slot.id,
      })),
    [uniqueSlots]
  );

  const destinationOptions = useMemo<DropdownItem[]>(
    () =>
      [...uniqueSlots, ...nonUniqueSlots]
        .filter((slot) => slot.id !== sourceId)
        .map((slot) => ({ label: slot.item.affix_name, value: slot.id })),
    [uniqueSlots, nonUniqueSlots, sourceId]
  );

  const affixOptions = useMemo<DropdownItem[]>(
    () => buildAffixOptions(selectedSource),
    [selectedSource]
  );

  const request = buildMoveQueenAffixesRequest(sourceId, destinationId, affix);

  const { submitting, error, move } = useMoveQueenAffixesApi({
    characterId,
    request,
  });

  const selectedCost =
    sourceId !== null && affix
      ? (data.costs.movement[sourceId]?.[affix] ?? null)
      : null;

  const canSubmit = request !== null && !submitting;

  const handleSelectSource = (option: DropdownItem): void => {
    const nextSourceId = Number(option.value);

    setSourceId(nextSourceId);
    setAffix(null);

    if (nextSourceId === destinationId) {
      setDestinationId(null);
    }
  };

  const handleSelectAffix = (option: DropdownItem): void => {
    setAffix(option.value as QueenAffixSelection);
  };

  const handleSelectDestination = (option: DropdownItem): void => {
    setDestinationId(Number(option.value));
  };

  const handleSubmit = async (): Promise<void> => {
    const response = await move();

    if (!response) {
      return;
    }

    onDataReplaced(response);
    onSuccess(response.message);
    setSourceId(null);
    setDestinationId(null);
    setAffix(null);
  };

  return {
    hasSourceSlots,
    sourceOptions,
    destinationOptions,
    affixOptions,
    selectedSourceId: sourceId,
    selectedDestinationId: destinationId,
    selectedAffix: affix,
    selectedCost,
    submitting,
    error,
    canSubmit,
    handleSelectSource,
    handleSelectAffix,
    handleSelectDestination,
    handleSubmit,
  };
};
