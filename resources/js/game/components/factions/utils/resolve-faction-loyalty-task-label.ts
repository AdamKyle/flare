import FactionLoyaltyTaskDefinition from '../api/definitions/faction-loyalty-task-definition';
import { FactionLoyaltyTaskType } from '../enums/faction-loyalty-task-type';

export const resolveFactionLoyaltyTaskLabel = (
  task: FactionLoyaltyTaskDefinition
): string => {
  if (task.type === FactionLoyaltyTaskType.BOUNTY) {
    return `Bounty: ${task.monster_name ?? 'Unknown monster'}`;
  }

  return `Craft: ${task.item_name ?? 'Unknown item'} (${task.type})`;
};
