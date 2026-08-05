import { useMemo, useState } from 'react';

import UseQueenMoveAffixesFlowDefinition, {
  UseQueenMoveAffixesFlowParams,
} from './definitions/use-queen-move-affixes-flow-definition';
import CraftingItemPreviewDefinition from '../../../shared/api/definitions/crafting-item-preview-definition';
import QueenInventorySlotDefinition from '../api/definitions/queen-inventory-slot-definition';
import { useMoveQueenAffixesApi } from '../api/hooks/use-move-queen-affixes-api';
import { useQueenDestinationItemsApi } from '../api/hooks/use-queen-destination-items-api';
import { useQueenUniqueItemsApi } from '../api/hooks/use-queen-unique-items-api';
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

  if (selectedSource.preview.item_prefix) {
    options.push({ label: 'Prefix', value: QueenAffixSelection.PREFIX });
  }

  if (selectedSource.preview.item_suffix) {
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
  const [sourceResultPreview, setSourceResultPreview] =
    useState<CraftingItemPreviewDefinition | null>(null);
  const [destinationResultPreview, setDestinationResultPreview] =
    useState<CraftingItemPreviewDefinition | null>(null);

  const sourceItemsApi = useQueenUniqueItemsApi({ character_id: characterId });
  const destinationItemsApi = useQueenDestinationItemsApi({
    character_id: characterId,
    source_slot_id: sourceId,
  });

  const hasSourceSlots = data.unique_slots.length > 0;

  const selectedSource = useMemo(
    () =>
      sourceItemsApi.loadedItems.find((slot) => slot.slot_id === sourceId) ??
      null,
    [sourceItemsApi.loadedItems, sourceId]
  );

  const selectedDestination = useMemo(
    () =>
      destinationItemsApi.loadedItems.find(
        (slot) => slot.slot_id === destinationId
      ) ?? null,
    [destinationItemsApi.loadedItems, destinationId]
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
    setDestinationId(null);
    destinationItemsApi.setSearchText('');
    setSourceResultPreview(null);
    setDestinationResultPreview(null);
  };

  const handleSelectAffix = (option: DropdownItem): void => {
    setAffix(option.value as QueenAffixSelection);
    setSourceResultPreview(null);
    setDestinationResultPreview(null);
  };

  const handleSelectDestination = (option: DropdownItem): void => {
    setDestinationId(Number(option.value));
    setSourceResultPreview(null);
    setDestinationResultPreview(null);
  };

  const handleSubmit = async (): Promise<void> => {
    setSourceResultPreview(null);
    setDestinationResultPreview(null);

    const response = await move();

    if (!response) {
      return;
    }

    onDataReplaced(response);
    onSuccess(response.message);
    setSourceResultPreview(response.source_result_preview ?? null);
    setDestinationResultPreview(response.destination_result_preview ?? null);
    setSourceId(null);
    setDestinationId(null);
    setAffix(null);
    sourceItemsApi.refresh();
  };

  return {
    hasSourceSlots,
    sourceItemsApi,
    destinationItemsApi,
    selectedSource,
    selectedDestination,
    affixOptions,
    selectedSourceId: sourceId,
    selectedDestinationId: destinationId,
    selectedAffix: affix,
    selectedCost,
    sourceResultPreview,
    destinationResultPreview,
    submitting,
    error,
    canSubmit,
    handleSelectSource,
    handleSelectAffix,
    handleSelectDestination,
    handleSubmit,
  };
};
