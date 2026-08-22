import CraftAndEnchantSetRequestDefinition, {
  CraftAndEnchantSetEnchantmentsDefinition,
} from '../api/definitions/craft-and-enchant-set-request-definition';
import { CraftSetPositionsDefinition } from '../api/definitions/craft-set-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { CraftAndEnchantBatchMode } from '../enums/craft-and-enchant-batch-mode';
import { CraftSetPosition } from '../enums/craft-set-position';
import { CRAFT_SET_REQUIRED_POSITIONS } from '../enums/craft-set-positions';
import CraftAndEnchantOutputSelection from '../types/craft-and-enchant-output-selection';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

interface BuildCraftAndEnchantSetRequestParams {
  selectedPositions: Partial<Record<CraftSetPosition, DropdownItem | null>>;
  enchantments: Partial<
    Record<
      CraftSetPosition,
      { prefix: DropdownItem | null; suffix: DropdownItem | null }
    >
  >;
  outputSelection: CraftAndEnchantOutputSelection;
}

export const buildCraftAndEnchantSetRequest = ({
  selectedPositions,
  enchantments,
  outputSelection,
}: BuildCraftAndEnchantSetRequestParams): CraftAndEnchantSetRequestDefinition | null => {
  const {
    disposition,
    output_destination: outputDestination,
    output_set_id: outputSetId,
    listing_price: listingPrice,
  } = outputSelection;

  const requiredPositionValues = CRAFT_SET_REQUIRED_POSITIONS.map(
    (position) => selectedPositions[position]?.value
  );

  if (
    !requiredPositionValues.every(
      (value): value is number => typeof value === 'number'
    )
  ) {
    return null;
  }

  if (disposition === BatchCraftingDisposition.KEEP && !outputDestination) {
    return null;
  }

  if (
    disposition === BatchCraftingDisposition.KEEP &&
    outputDestination === BatchCraftingOutputDestination.INVENTORY_SET &&
    !outputSetId
  ) {
    return null;
  }

  if (disposition === BatchCraftingDisposition.LIST && !listingPrice) {
    return null;
  }

  const includedPositions = CRAFT_SET_REQUIRED_POSITIONS.filter(
    (position) => typeof selectedPositions[position]?.value === 'number'
  );

  const hasEnchantmentForEveryIncludedPosition = includedPositions.every(
    (position) => {
      const entry = enchantments[position];

      return (
        typeof entry?.prefix?.value === 'number' ||
        typeof entry?.suffix?.value === 'number'
      );
    }
  );

  if (!hasEnchantmentForEveryIncludedPosition) {
    return null;
  }

  const [
    body,
    leggings,
    sleeves,
    gloves,
    feet,
    helmet,
    ring_0,
    ring_1,
    spellDamage,
    spellHealing,
  ] = requiredPositionValues;
  const leftHand = selectedPositions[CraftSetPosition.LEFT_HAND]?.value;
  const rightHand = selectedPositions[CraftSetPosition.RIGHT_HAND]?.value;

  const positions: CraftSetPositionsDefinition = {
    body,
    leggings,
    sleeves,
    gloves,
    feet,
    helmet,
    ring_0,
    ring_1,
    'spell-damage': spellDamage,
    'spell-healing': spellHealing,
    ...(typeof leftHand === 'number' ? { left_hand: leftHand } : {}),
    ...(typeof rightHand === 'number' ? { right_hand: rightHand } : {}),
  };

  const enchantmentEntries: CraftAndEnchantSetEnchantmentsDefinition = {};

  Object.entries(selectedPositions).forEach(([positionKey, item]) => {
    if (typeof item?.value !== 'number') {
      return;
    }

    const position = positionKey as CraftSetPosition;
    const entry = enchantments[position];

    const key = position as unknown as keyof CraftSetPositionsDefinition;

    enchantmentEntries[key] = {
      prefix_id:
        typeof entry?.prefix?.value === 'number' ? entry.prefix.value : null,
      suffix_id:
        typeof entry?.suffix?.value === 'number' ? entry.suffix.value : null,
    };
  });

  return {
    batch_type: BatchCraftingType.CRAFT_AND_ENCHANT,
    disposition,
    progress: {
      craft_enchant_mode: CraftAndEnchantBatchMode.SET,
      set_positions: positions,
      enchantments: enchantmentEntries,
      ...(disposition === BatchCraftingDisposition.KEEP && outputDestination
        ? {
            output_destination: outputDestination,
            ...(outputDestination ===
              BatchCraftingOutputDestination.INVENTORY_SET && outputSetId
              ? { output_set_id: outputSetId }
              : {}),
          }
        : {}),
      ...(disposition === BatchCraftingDisposition.LIST && listingPrice
        ? { listing_price: listingPrice }
        : {}),
    },
  };
};
