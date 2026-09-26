import FactionLoyaltyNpcDefinition from '../../api/definitions/faction-loyalty-npc-definition';

export default interface FactionLoyaltyNpcCardProps {
  faction_loyalty_npc: FactionLoyaltyNpcDefinition;
  on_open: (factionLoyaltyNpcId: number) => void;
}
