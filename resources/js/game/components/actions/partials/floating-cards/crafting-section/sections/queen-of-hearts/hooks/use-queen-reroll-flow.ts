import { useMemo, useState } from 'react';

import UseQueenRerollFlowDefinition, {
  UseQueenRerollFlowParams,
} from './definitions/use-queen-reroll-flow-definition';
import CraftingItemPreviewDefinition from '../../../shared/api/definitions/crafting-item-preview-definition';
import QueenInventorySlotDefinition from '../api/definitions/queen-inventory-slot-definition';
import { useQueenUniqueItemsApi } from '../api/hooks/use-queen-unique-items-api';
import { useRerollQueenAffixApi } from '../api/hooks/use-reroll-queen-affix-api';
import { QueenAffixSelection } from '../enums/queen-affix-selection';
import { QueenRerollType } from '../enums/queen-reroll-type';
import { buildRerollQueenAffixRequest } from '../utils/build-reroll-queen-affix-request';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const rerollTypeOptions: DropdownItem[] = [
  { label: 'Base Details', value: QueenRerollType.BASE },
  { label: 'Core Stats', value: QueenRerollType.STATS },
  { label: 'Skill Modifiers', value: QueenRerollType.SKILLS },
  { label: 'Damage Modifiers', value: QueenRerollType.DAMAGE },
  { label: 'Resistances', value: QueenRerollType.RESISTANCE },
  { label: 'All of it', value: QueenRerollType.EVERYTHING },
];

const buildAffixOptions = (
  selectedSlot: QueenInventorySlotDefinition | null
): DropdownItem[] => {
  if (!selectedSlot) {
    return [];
  }

  const options: DropdownItem[] = [];

  if (selectedSlot.preview.item_prefix) {
    options.push({ label: 'Prefix', value: QueenAffixSelection.PREFIX });
  }

  if (selectedSlot.preview.item_suffix) {
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

export const useQueenRerollFlow = ({
  characterId,
  data,
  onDataReplaced,
}: UseQueenRerollFlowParams): UseQueenRerollFlowDefinition => {
  const [slotId, setSlotId] = useState<number | null>(null);
  const [affix, setAffix] = useState<QueenAffixSelection | null>(null);
  const [rerollType, setRerollType] = useState<QueenRerollType | null>(null);
  const [resultPreview, setResultPreview] =
    useState<CraftingItemPreviewDefinition | null>(null);
  const [resultMessage, setResultMessage] = useState<string | null>(null);

  const itemsApi = useQueenUniqueItemsApi({ character_id: characterId });

  const hasSlots = data.unique_slots.length > 0;

  const selectedSlot = useMemo(
    () => itemsApi.loadedItems.find((slot) => slot.slot_id === slotId) ?? null,
    [itemsApi.loadedItems, slotId]
  );

  const affixOptions = useMemo<DropdownItem[]>(
    () => buildAffixOptions(selectedSlot),
    [selectedSlot]
  );

  const request = buildRerollQueenAffixRequest(slotId, affix, rerollType);

  const { submitting, error, reroll } = useRerollQueenAffixApi({
    characterId,
    request,
  });

  const selectedCost =
    affix && rerollType
      ? (data.costs.reroll[affix]?.[rerollType] ?? null)
      : null;

  const canSubmit = request !== null && !submitting;

  const handleSelectSlot = (option: DropdownItem): void => {
    setSlotId(Number(option.value));
    setAffix(null);
    setRerollType(null);
    setResultPreview(null);
    setResultMessage(null);
  };

  const handleSelectAffix = (option: DropdownItem): void => {
    setAffix(option.value as QueenAffixSelection);
    setResultPreview(null);
    setResultMessage(null);
  };

  const handleSelectRerollType = (option: DropdownItem): void => {
    setRerollType(option.value as QueenRerollType);
    setResultPreview(null);
    setResultMessage(null);
  };

  const handleSubmit = async (): Promise<void> => {
    setResultPreview(null);
    setResultMessage(null);

    const response = await reroll();

    if (!response) {
      return;
    }

    onDataReplaced(response);
    setResultMessage(response.message ?? null);
    setResultPreview(response.result_preview ?? null);
  };

  return {
    hasSlots,
    itemsApi,
    affixOptions,
    rerollTypeOptions,
    selectedSlotId: slotId,
    selectedSlot,
    selectedAffix: affix,
    selectedRerollType: rerollType,
    selectedCost,
    resultPreview,
    resultMessage,
    submitting,
    error,
    canSubmit,
    handleSelectSlot,
    handleSelectAffix,
    handleSelectRerollType,
    handleSubmit,
  };
};
