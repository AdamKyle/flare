import { hasIncompleteFactionLoyaltyTasks } from './has-incomplete-faction-loyalty-tasks';
import FactionLoyaltyNpcDefinition from '../api/definitions/faction-loyalty-npc-definition';

import ActiveAutomationDefinition from 'game-data/api-data-definitions/character/active-automation-definition';

/**
 * Mirrors the already-known facts the backend start endpoint rejects on, so
 * the start action can be disabled with the same explanation; the backend
 * still validates every start.
 */
export const resolveFactionLoyaltyAutomationBlocker = (
  factionLoyaltyNpc: FactionLoyaltyNpcDefinition,
  characterGameMapId: number,
  activeAutomation: ActiveAutomationDefinition | null
): string | null => {
  if (activeAutomation !== null) {
    return `You are currently busy with ${activeAutomation.name} automation.`;
  }

  if (!factionLoyaltyNpc.currently_helping) {
    return 'You must be assisting this NPC before automating faction loyalty.';
  }

  if (factionLoyaltyNpc.npc.game_map_id !== characterGameMapId) {
    return 'You must be on the same map as the NPC you are assisting.';
  }

  if (!hasIncompleteFactionLoyaltyTasks(factionLoyaltyNpc)) {
    return 'This NPC does not have any incomplete tasks for you to automate.';
  }

  return null;
};
