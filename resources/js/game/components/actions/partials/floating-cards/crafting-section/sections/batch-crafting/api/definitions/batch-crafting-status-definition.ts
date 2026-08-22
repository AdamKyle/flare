import BatchCraftingChartPointDefinition from './batch-crafting-chart-point-definition';
import BatchCraftingDestinationCapacityDefinition from './batch-crafting-destination-capacity-definition';
import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingEndReason } from '../../enums/batch-crafting-end-reason';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';
import { BatchCraftingStatus } from '../../enums/batch-crafting-status';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { CraftAndEnchantSetPhase } from '../../enums/craft-and-enchant-set-phase';
import { CraftSetPosition } from '../../enums/craft-set-position';
import { CraftingBatchMode } from '../../enums/crafting-batch-mode';
import { CraftingSkillGroup } from '../../enums/crafting-skill-group';
import { EnchantEventPhase } from '../../enums/enchant-event-phase';

export interface BatchCraftingCraftingSkillFactDefinition {
  crafting_type: CraftingSkillGroup;
  skill_name: string;
  level: number;
  max_level: number;
  current_xp: number;
  next_level_xp: number;
  is_maxed: boolean;
}

export interface BatchCraftingCapabilitiesDefinition {
  can_craft_for_experience: boolean;
  can_craft_for_event: boolean;
  crafting_skills: BatchCraftingCraftingSkillFactDefinition[];
  event_goal: BatchCraftingEventGoalFactsDefinition | null;
  can_craft_and_enchant: boolean;
  can_craft_and_enchant_for_experience: boolean;
  enchanting_skill: BatchCraftingEnchantingSkillFactDefinition | null;
  can_enchant_for_event: boolean;
  enchant_event_goal: BatchCraftingEnchantEventGoalFactsDefinition | null;
  can_alchemy: boolean;
  can_alchemy_for_experience: boolean;
  alchemy_skill: BatchCraftingSkillFactDefinition | null;
  can_holy_oils: boolean;
  can_trinketry: boolean;
  trinketry_skill: BatchCraftingSkillFactDefinition | null;
}

export interface BatchCraftingSkillFactDefinition {
  skill_name: string;
  level: number;
  max_level: number;
  current_xp: number;
  next_level_xp: number;
  is_maxed: boolean;
}

export interface BatchCraftingEventGoalFactsDefinition {
  goal_id: number;
  max_crafts: number | null;
  total_crafts: number;
  next_reward_at: number;
  reward_every: number;
  character_contribution: number;
}

export interface BatchCraftingEnchantingSkillFactDefinition {
  skill_name: string;
  level: number;
  max_level: number;
  current_xp: number;
  next_level_xp: number;
  is_maxed: boolean;
}

export interface BatchCraftingEnchantEventGoalFactsDefinition {
  goal_id: number;
  event_id: number | null;
  event_type: number | null;
  max_enchants: number | null;
  total_enchants: number;
  remaining_enchants: number;
  next_reward_at: number;
  reward_every: number;
  character_contribution: number;
  ends_at: string | null;
}

export interface BatchCraftingSetProgressDefinition {
  total_entries: number;
  completed_entries: number;
  remaining_entries: number;
  current_position: CraftSetPosition | null;
  /** Craft and Enchant Set only: the position's current crafting/enchanting phase. */
  current_phase?: CraftAndEnchantSetPhase;
}

export interface BatchCraftingExperienceProgressDefinition {
  actions_per_minute: number;
  current_cycle_position: number;
  cycle_size: number;
  current_crafting_type: CraftingSkillGroup | null;
  crafting_xp_gained: number;
  crafting_skills: BatchCraftingCraftingSkillFactDefinition[];
  /** Craft and Enchant For Experience only. */
  enchanting_xp_gained?: number;
  enchanting_skill?: BatchCraftingEnchantingSkillFactDefinition | null;
}

export interface BatchCraftingEventProgressDefinition {
  actions_per_minute: number;
  current_crafting_type: CraftingSkillGroup | null;
  skipped_count: number;
  crafting_xp_gained: number;
  goal_id: number | null;
  max_crafts: number | null;
  total_crafts: number | null;
  next_reward_at: number | null;
  reward_every: number | null;
  character_contribution: number | null;
  /** Enchant For Event only. */
  phase?: EnchantEventPhase;
  enchanting_xp_gained?: number;
  event_id?: number | null;
  event_type?: number | null;
  max_enchants?: number | null;
  total_enchants?: number | null;
  remaining_enchants?: number | null;
  ends_at?: string | null;
}

export interface BatchCraftingAlchemyAmountProgressDefinition {
  current_item_id: number | null;
  current_item_name: string | null;
  requested_amount: number;
  completed_amount: number;
  remaining_amount: number;
  alchemy_xp_gained: number;
}

export interface BatchCraftingAlchemyExperienceProgressDefinition {
  current_item_id: number | null;
  current_item_name: string | null;
  actions_per_minute: number;
  alchemy_xp_gained: number;
  alchemy_skill: BatchCraftingSkillFactDefinition | null;
}

export interface BatchCraftingHolyOilsProgressDefinition {
  current_target_item_id: number | null;
  current_target_item_name: string | null;
  current_oil_item_id: number | null;
  current_oil_item_name: string | null;
  current_holy_stacks: number | null;
  max_holy_stacks: number | null;
  total_planned_targets: number;
  completed_targets: number;
  remaining_targets: number;
  inventory_set_id: number | null;
  inventory_set_name: string | null;
}

export interface BatchCraftingTrinketryProgressDefinition {
  current_item_id: number | null;
  current_item_name: string | null;
  actions_per_minute: number;
  trinketry_xp_gained: number;
  trinketry_skill: BatchCraftingSkillFactDefinition | null;
  destination_set_id: number | null;
  destination_set_name: string | null;
}

export interface BatchCraftingBatchStatusDefinition {
  id: number;
  batch_type: BatchCraftingType;
  /** The authoritative type+mode routing discriminator. Prefer this over craft_mode. */
  mode: string;
  /** @deprecated Craft-only compatibility field. Use batch_type + mode for routing. */
  craft_mode: CraftingBatchMode;
  disposition: BatchCraftingDisposition;
  status: BatchCraftingStatus;
  ended_reason: BatchCraftingEndReason | null;
  current_item_id: number | null;
  current_item_name: string | null;
  current_crafting_type: CraftingSkillGroup | null;
  current_prefix_name: string | null;
  current_suffix_name: string | null;
  output_destination: BatchCraftingOutputDestination | null;
  listing_price: number | null;
  destination_set_id: number | null;
  destination_set_name: string | null;
  requested_amount: number | null;
  completed_amount: number | null;
  remaining_amount: number | null;
  crafted_count: number;
  kept_count: number;
  sold_count: number;
  destroyed_count: number;
  listed_count: number;
  applied_count: number;
  disenchanted_count: number;
  used_count: number;
  failed_count: number;
  skipped_count: number;
  gold_spent: number;
  gold_gained: number;
  gold_left: number;
  gold_dust_spent: number;
  gold_dust_left: number;
  shards_spent: number;
  shards_left: number;
  copper_coins_spent: number;
  copper_coins_left: number;
  started_at: string;
  scheduled_for: string;
  processing_started_at: string | null;
  next_attempt_at: string | null;
  completed_at: string | null;
  destination_capacity: BatchCraftingDestinationCapacityDefinition | null;
  chart_points: BatchCraftingChartPointDefinition[];
  set_progress: BatchCraftingSetProgressDefinition | null;
  experience_progress: BatchCraftingExperienceProgressDefinition | null;
  event_progress: BatchCraftingEventProgressDefinition | null;
  alchemy_amount_progress: BatchCraftingAlchemyAmountProgressDefinition | null;
  alchemy_experience_progress: BatchCraftingAlchemyExperienceProgressDefinition | null;
  holy_oils_progress: BatchCraftingHolyOilsProgressDefinition | null;
  trinketry_progress: BatchCraftingTrinketryProgressDefinition | null;
}

export default interface BatchCraftingStatusDefinition {
  active: boolean;
  is_running: boolean;
  is_scheduled: boolean;
  is_processing: boolean;
  is_waiting: boolean;
  is_visible: boolean;
  can_cancel: boolean;
  can_dismiss: boolean;
  show_info: boolean;
  /**
   * Authoritative setup capabilities. Present on the initial/reopen GET and on the
   * no-visible-batch state. `null` on a runtime broadcast for an already-visible batch, which
   * never recalculates them — the status provider retains the last authoritative value it saw.
   */
  capabilities: BatchCraftingCapabilitiesDefinition | null;
  batch: BatchCraftingBatchStatusDefinition | null;
}
