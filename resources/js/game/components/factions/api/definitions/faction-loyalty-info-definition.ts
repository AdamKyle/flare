import FactionLoyaltyDefinition from './faction-loyalty-definition';

export interface FactionLoyaltyNpcNameDefinition {
  id: number;
  name: string;
}

export default interface FactionLoyaltyInfoDefinition {
  npcs: FactionLoyaltyNpcNameDefinition[];
  faction_loyalty: FactionLoyaltyDefinition;
  map_name: string;
  must_revive: boolean;
  attack_type: string;
}
