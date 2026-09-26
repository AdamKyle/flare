import DelveCurrentFoeDefinition from './delve-current-foe-definition';
import DelveQuestItemDefinition from './delve-quest-item-definition';
import DelveRewardCheckpointDefinition from './delve-reward-checkpoint-definition';

export interface DelveInactiveStatusDefinition {
  active: false;
  completed: false;
}

interface DelveRunStatusDefinition {
  started_at: string;
  elapsed_seconds: number;
  increase_enemy_strength: number | null;
  increase_percentage: number;
  quest_items: DelveQuestItemDefinition[];
  reward_checkpoints: DelveRewardCheckpointDefinition[];
  monster_name: string | null;
  enemy_stats_available: boolean;
  current_foe: DelveCurrentFoeDefinition;
}

export interface DelveActiveStatusDefinition extends DelveRunStatusDefinition {
  active: true;
  completed: false;
  quest_item_drop_hours_required: number | null;
  quest_item_drop_seconds_remaining: number | null;
  quest_item_drop_available_at: string | null;
  quest_item_drop_available: boolean;
}

export interface DelveCompletedStatusDefinition extends DelveRunStatusDefinition {
  active: false;
  completed: true;
  id: number;
  completed_at: string;
  reason: string;
  message: string;
}

export type DelveStatusDefinition =
  | DelveInactiveStatusDefinition
  | DelveActiveStatusDefinition
  | DelveCompletedStatusDefinition;
