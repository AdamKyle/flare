export default interface FactionLoyaltyScreenProps {
  character_id: number;
  on_open_npc: (factionLoyaltyNpcId: number) => void;
}
