import FactionLoyaltyNpcDefinition from './faction-loyalty-npc-definition';

export default interface FactionLoyaltyDefinition {
  id: number;
  character_id: number;
  faction_id: number;
  is_pledged: boolean;
  faction_loyalty_npcs: FactionLoyaltyNpcDefinition[];
}
