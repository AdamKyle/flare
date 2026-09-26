import FactionLoyaltyTaskDefinition from './faction-loyalty-task-definition';
import FactionLoyaltyWarningNoticeDefinition from './faction-loyalty-warning-notice-definition';

export interface FactionLoyaltyNpcIdentityDefinition {
  id: number;
  name: string;
  real_name: string;
  game_map_id: number;
  x_position: number;
  y_position: number;
}

export interface FactionLoyaltyNpcTasksDefinition {
  id: number;
  fame_tasks: FactionLoyaltyTaskDefinition[];
}

export default interface FactionLoyaltyNpcDefinition {
  id: number;
  faction_loyalty_id: number;
  npc_id: number;
  current_level: number;
  max_level: number;
  next_level_fame: number;
  currently_helping: boolean;
  kingdom_item_defence_bonus: number;
  current_fame: number;
  current_kingdom_item_defence_bonus: number;
  npc: FactionLoyaltyNpcIdentityDefinition;
  faction_loyalty_npc_tasks: FactionLoyaltyNpcTasksDefinition | null;
  faction_loyalty_warning_notices?: FactionLoyaltyWarningNoticeDefinition[];
}
