import { AlchemyBatchMode } from '../enums/alchemy-batch-mode';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingEndReason } from '../enums/batch-crafting-end-reason';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { CraftAndEnchantBatchMode } from '../enums/craft-and-enchant-batch-mode';
import { CraftSetPosition } from '../enums/craft-set-position';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';
import { CraftingSkillGroup } from '../enums/crafting-skill-group';
import { EnchantEventPhase } from '../enums/enchant-event-phase';
import { HolyOilsBatchMode } from '../enums/holy-oils-batch-mode';

const CRAFT_MODE_LABELS: Record<CraftingBatchMode, string> = {
  [CraftingBatchMode.AMOUNT]: 'Craft Amount',
  [CraftingBatchMode.EXPERIENCE]: 'Craft For Experience',
  [CraftingBatchMode.SET]: 'Craft Set',
  [CraftingBatchMode.EVENT]: 'Craft For Event',
};

const BATCH_TYPE_LABELS: Record<BatchCraftingType, string> = {
  [BatchCraftingType.CRAFT]: 'Craft',
  [BatchCraftingType.CRAFT_AND_ENCHANT]: 'Craft and Enchant',
  [BatchCraftingType.ENCHANT]: 'Enchant For Event',
  [BatchCraftingType.ALCHEMY]: 'Alchemy',
  [BatchCraftingType.HOLY_OILS]: 'Holy Oils',
  [BatchCraftingType.TRINKETRY]: 'Trinketry',
};

const CRAFT_AND_ENCHANT_MODE_LABELS: Record<CraftAndEnchantBatchMode, string> =
  {
    [CraftAndEnchantBatchMode.AMOUNT]: 'Craft and Enchant Amount',
    [CraftAndEnchantBatchMode.EXPERIENCE]: 'Craft and Enchant For Experience',
    [CraftAndEnchantBatchMode.SET]: 'Craft and Enchant Set',
  };

const ALCHEMY_MODE_LABELS: Record<AlchemyBatchMode, string> = {
  [AlchemyBatchMode.AMOUNT]: 'Alchemy Amount',
  [AlchemyBatchMode.EXPERIENCE]: 'Alchemy For Experience',
};

const HOLY_OILS_MODE_LABELS: Record<HolyOilsBatchMode, string> = {
  [HolyOilsBatchMode.SELECTED_ITEMS]: 'Selected Items',
  [HolyOilsBatchMode.INVENTORY_SET]: 'Inventory Set',
};

const EVENT_ENCHANT_PHASE_LABELS: Record<EnchantEventPhase, string> = {
  [EnchantEventPhase.ENCHANT_EVENT_INVENTORY]: 'Enchanting Event Items',
  [EnchantEventPhase.CRAFT_FALLBACK_SET]: 'Crafting Fallback Items',
  [EnchantEventPhase.ENCHANT_FALLBACK_SET]: 'Enchanting Fallback Items',
};

const DISPOSITION_LABELS: Record<BatchCraftingDisposition, string> = {
  [BatchCraftingDisposition.KEEP]: 'Keep',
  [BatchCraftingDisposition.SELL]: 'Sell',
  [BatchCraftingDisposition.DESTROY]: 'Destroy',
  [BatchCraftingDisposition.LIST]: 'List',
  [BatchCraftingDisposition.DISENCHANT]: 'Disenchant',
  [BatchCraftingDisposition.USE_NOW]: 'Use Now',
  [BatchCraftingDisposition.KEEP_BEST_SELL_REST]: 'Keep Best and Sell Rest',
  [BatchCraftingDisposition.KEEP_BEST_DESTROY_REST]:
    'Keep Best and Destroy Rest',
  [BatchCraftingDisposition.KEEP_BEST_DISENCHANT_REST]:
    'Keep Best and Disenchant Rest',
};

const OUTPUT_DESTINATION_LABELS: Record<
  BatchCraftingOutputDestination,
  string
> = {
  [BatchCraftingOutputDestination.INVENTORY]: 'Inventory',
  [BatchCraftingOutputDestination.CRAFTED_ITEMS_SET]: 'Crafted Items Set',
  [BatchCraftingOutputDestination.INVENTORY_SET]: 'Specified Empty Set',
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
  [BatchCraftingEndReason.NO_GOLD_DUST]: 'Ran out of Gold Dust',
  [BatchCraftingEndReason.NO_SHARDS]: 'Ran out of Shards',
  [BatchCraftingEndReason.NO_COPPER_COINS]: 'Ran out of Copper Coins',
  [BatchCraftingEndReason.NO_ENCHANTING_AFFIX]:
    'No eligible Enchantment remained',
  [BatchCraftingEndReason.INT_TOO_LOW]: 'Intelligence is too low',
  [BatchCraftingEndReason.NO_ALCHEMY_ITEMS]: 'Nothing left to transmute',
  [BatchCraftingEndReason.NO_HOLY_OILS]: 'No Holy Oils remained',
  [BatchCraftingEndReason.NO_HOLY_OIL_TARGETS]: 'No eligible targets remained',
  [BatchCraftingEndReason.NO_TRINKETRY_ITEMS]: 'Nothing left to craft',
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

export const batchTypeLabel = (batchType: BatchCraftingType): string =>
  BATCH_TYPE_LABELS[batchType];

export const craftAndEnchantModeLabel = (
  mode: CraftAndEnchantBatchMode
): string => CRAFT_AND_ENCHANT_MODE_LABELS[mode];

export const alchemyModeLabel = (mode: AlchemyBatchMode): string =>
  ALCHEMY_MODE_LABELS[mode];

export const holyOilsModeLabel = (mode: HolyOilsBatchMode): string =>
  HOLY_OILS_MODE_LABELS[mode];

export const eventEnchantPhaseLabel = (
  phase: EnchantEventPhase | undefined
): string => (phase && EVENT_ENCHANT_PHASE_LABELS[phase]) ?? '—';

export const endReasonLabel = (
  reason: BatchCraftingEndReason | null
): string | null => {
  if (!reason) {
    return null;
  }

  return END_REASON_LABELS[reason];
};
