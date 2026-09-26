import FactionLoyaltyNpcDefinition from '../api/definitions/faction-loyalty-npc-definition';

export const hasIncompleteFactionLoyaltyTasks = (
  factionLoyaltyNpc: FactionLoyaltyNpcDefinition
): boolean =>
  (factionLoyaltyNpc.faction_loyalty_npc_tasks?.fame_tasks ?? []).some(
    (task) => task.current_amount < task.required_amount
  );
