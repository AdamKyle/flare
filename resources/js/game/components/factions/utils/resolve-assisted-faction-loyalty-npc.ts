import FactionLoyaltyInfoDefinition from '../api/definitions/faction-loyalty-info-definition';
import FactionLoyaltyNpcDefinition from '../api/definitions/faction-loyalty-npc-definition';

export const resolveAssistedFactionLoyaltyNpc = (
  info: FactionLoyaltyInfoDefinition | null
): FactionLoyaltyNpcDefinition | null => {
  if (info === null) {
    return null;
  }

  return (
    info.faction_loyalty.faction_loyalty_npcs.find(
      (factionLoyaltyNpc) => factionLoyaltyNpc.currently_helping
    ) ?? null
  );
};
