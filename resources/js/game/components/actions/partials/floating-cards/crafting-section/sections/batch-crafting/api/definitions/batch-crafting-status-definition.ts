import BatchCraftingChartPointDefinition from './batch-crafting-chart-point-definition';
import BatchCraftingDestinationCapacityDefinition from './batch-crafting-destination-capacity-definition';
import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingEndReason } from '../../enums/batch-crafting-end-reason';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';
import { BatchCraftingStatus } from '../../enums/batch-crafting-status';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { CraftSetPosition } from '../../enums/craft-set-position';
import { CraftingBatchMode } from '../../enums/crafting-batch-mode';
import { CraftingSkillGroup } from '../../enums/crafting-skill-group';

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
}

export interface BatchCraftingEventGoalFactsDefinition {
  goal_id: number;
  max_crafts: number | null;
  total_crafts: number;
  next_reward_at: number;
  reward_every: number;
  character_contribution: number;
}

export interface BatchCraftingSetProgressDefinition {
  total_entries: number;
  completed_entries: number;
  remaining_entries: number;
  current_position: CraftSetPosition | null;
}

export interface BatchCraftingExperienceProgressDefinition {
  actions_per_minute: number;
  current_cycle_position: number;
  cycle_size: number;
  current_crafting_type: CraftingSkillGroup | null;
  crafting_xp_gained: number;
  crafting_skills: BatchCraftingCraftingSkillFactDefinition[];
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
}

export interface BatchCraftingBatchStatusDefinition {
  id: number;
  batch_type: BatchCraftingType;
  craft_mode: CraftingBatchMode;
  disposition: BatchCraftingDisposition;
  status: BatchCraftingStatus;
  ended_reason: BatchCraftingEndReason | null;
  current_item_id: number | null;
  current_item_name: string | null;
  current_crafting_type: CraftingSkillGroup | null;
  output_destination: BatchCraftingOutputDestination | null;
  destination_set_id: number | null;
  destination_set_name: string | null;
  requested_amount: number | null;
  completed_amount: number | null;
  remaining_amount: number | null;
  crafted_count: number;
  kept_count: number;
  sold_count: number;
  destroyed_count: number;
  failed_count: number;
  skipped_count: number;
  gold_spent: number;
  gold_gained: number;
  gold_left: number;
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
