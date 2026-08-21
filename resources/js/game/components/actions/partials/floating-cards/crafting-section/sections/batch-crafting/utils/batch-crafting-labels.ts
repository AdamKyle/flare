import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingEndReason } from '../enums/batch-crafting-end-reason';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';
import { CraftSetPosition } from '../enums/craft-set-position';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';
import { CraftingSkillGroup } from '../enums/crafting-skill-group';

const CRAFT_MODE_LABELS: Record<CraftingBatchMode, string> = {
  [CraftingBatchMode.AMOUNT]: 'Craft Amount',
  [CraftingBatchMode.EXPERIENCE]: 'Craft For Experience',
  [CraftingBatchMode.SET]: 'Craft Set',
  [CraftingBatchMode.EVENT]: 'Craft For Event',
};

const DISPOSITION_LABELS: Record<BatchCraftingDisposition, string> = {
  [BatchCraftingDisposition.KEEP]: 'Keep',
  [BatchCraftingDisposition.SELL]: 'Sell',
  [BatchCraftingDisposition.DESTROY]: 'Destroy',
  [BatchCraftingDisposition.KEEP_BEST_SELL_REST]: 'Keep Best and Sell Rest',
  [BatchCraftingDisposition.KEEP_BEST_DESTROY_REST]:
    'Keep Best and Destroy Rest',
};

const OUTPUT_DESTINATION_LABELS: Record<
  BatchCraftingOutputDestination,
  string
> = {
  [BatchCraftingOutputDestination.INVENTORY]: 'Inventory',
  [BatchCraftingOutputDestination.CRAFTED_ITEMS_SET]: 'Crafted Items Set',
  [BatchCraftingOutputDestination.INVENTORY_SET]: 'Inventory Set',
};

const END_REASON_LABELS: Record<BatchCraftingEndReason, string> = {
  [BatchCraftingEndReason.COMPLETED_DURATION]: 'Reached the 8 hour limit',
  [BatchCraftingEndReason.DIED]: 'You died',
  [BatchCraftingEndReason.NO_GOLD]: 'Ran out of Gold',
  [BatchCraftingEndReason.NO_INVENTORY_SPACE]: 'Inventory is full',
  [BatchCraftingEndReason.MAXED_OR_NOTHING_LEFT]: 'Nothing left to craft',
  [BatchCraftingEndReason.CANCELLED]: 'Cancelled',
  [BatchCraftingEndReason.FAILED]: 'An unexpected error stopped the batch',
  [BatchCraftingEndReason.AMOUNT_REACHED]: 'Requested amount reached',
  [BatchCraftingEndReason.BATCH_CRAFTING_SET_FULL]: 'Crafted Items Set is full',
  [BatchCraftingEndReason.SKILL_MAXED]: 'All Crafting skills are maxed',
  [BatchCraftingEndReason.CRAFT_SET_COMPLETE]: 'Set completed',
  [BatchCraftingEndReason.CRAFT_SET_FULL]: 'Destination set is full',
  [BatchCraftingEndReason.EVENT_GOAL_COMPLETE]: 'Event goal completed',
  [BatchCraftingEndReason.EVENT_NOT_RUNNING]:
    'The Craft Event is no longer running',
  [BatchCraftingEndReason.EVENT_WRONG_MAP]: 'You left the Event map',
  [BatchCraftingEndReason.EVENT_STEP_CHANGED]: 'The Event moved past Crafting',
  [BatchCraftingEndReason.EVENT_NO_CRAFTABLE_ITEMS]:
    'No craftable items remained for the Event',
};

const CRAFTING_DISCIPLINE_LABELS: Record<CraftingSkillGroup, string> = {
  [CraftingSkillGroup.WEAPON]: 'Weapon Crafting',
  [CraftingSkillGroup.ARMOUR]: 'Armour Crafting',
  [CraftingSkillGroup.RING]: 'Ring Crafting',
  [CraftingSkillGroup.SPELL]: 'Spell Crafting',
};

export const craftingDisciplineLabel = (
  craftingType: CraftingSkillGroup | null
): string | null => {
  if (!craftingType) {
    return null;
  }

  return CRAFTING_DISCIPLINE_LABELS[craftingType] ?? null;
};

const CRAFT_SET_POSITION_LABELS: Record<CraftSetPosition, string> = {
  [CraftSetPosition.LEFT_HAND]: 'Left Hand',
  [CraftSetPosition.RIGHT_HAND]: 'Right Hand',
  [CraftSetPosition.BODY]: 'Body',
  [CraftSetPosition.LEGGINGS]: 'Leggings',
  [CraftSetPosition.SLEEVES]: 'Sleeves',
  [CraftSetPosition.GLOVES]: 'Gloves',
  [CraftSetPosition.FEET]: 'Feet',
  [CraftSetPosition.HELMET]: 'Helmet',
  [CraftSetPosition.RING_ONE]: 'Ring 1',
  [CraftSetPosition.RING_TWO]: 'Ring 2',
  [CraftSetPosition.DAMAGE_SPELL]: 'Damage Spell',
  [CraftSetPosition.HEALING_SPELL]: 'Healing Spell',
};

export const craftSetPositionLabel = (
  position: CraftSetPosition | null
): string | null => {
  if (!position) {
    return null;
  }

  return CRAFT_SET_POSITION_LABELS[position];
};

export const dispositionLabel = (
  disposition: BatchCraftingDisposition
): string => DISPOSITION_LABELS[disposition];

export const outputDestinationLabel = (
  destination: BatchCraftingOutputDestination | null
): string | null => {
  if (!destination) {
    return null;
  }

  return OUTPUT_DESTINATION_LABELS[destination];
};

export const craftModeLabel = (mode: CraftingBatchMode): string =>
  CRAFT_MODE_LABELS[mode];

export const endReasonLabel = (
  reason: BatchCraftingEndReason | null
): string | null => {
  if (!reason) {
    return null;
  }

  return END_REASON_LABELS[reason];
};
