import FactionLoyaltyNpcDefinition from '../api/definitions/faction-loyalty-npc-definition';

export const isFactionLoyaltyNpcMastered = (
  factionLoyaltyNpc: FactionLoyaltyNpcDefinition
): boolean => factionLoyaltyNpc.current_level >= factionLoyaltyNpc.max_level;
