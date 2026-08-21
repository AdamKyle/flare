import CraftSetRequestDefinition, {
  CraftSetPositionsDefinition,
} from '../api/definitions/craft-set-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { CraftSetPosition } from '../enums/craft-set-position';
import { CRAFT_SET_REQUIRED_POSITIONS } from '../enums/craft-set-positions';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';
import CraftSetOutputSelection from '../types/craft-set-output-selection';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

interface BuildCraftSetRequestParams {
  selectedPositions: Partial<Record<CraftSetPosition, DropdownItem | null>>;
  outputSelection: CraftSetOutputSelection;
}

export const buildCraftSetRequest = ({
  selectedPositions,
  outputSelection,
}: BuildCraftSetRequestParams): CraftSetRequestDefinition | null => {
  const { disposition, output_destination: outputDestination } =
    outputSelection;
  const outputSetId = outputSelection.output_set_id;

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

  return {
    batch_type: BatchCraftingType.CRAFT,
    disposition,
    progress: {
      craft_mode: CraftingBatchMode.SET,
      set_positions: positions,
      ...(disposition === BatchCraftingDisposition.KEEP && outputDestination
        ? {
            output_destination: outputDestination,
            ...(outputDestination ===
              BatchCraftingOutputDestination.INVENTORY_SET && outputSetId
              ? { output_set_id: outputSetId }
              : {}),
          }
        : {}),
    },
  };
};
