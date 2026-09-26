import FactionLoyaltyNpcDefinition from '../../api/definitions/faction-loyalty-npc-definition';

export default interface FactionLoyaltyNpcContentProps {
  character_id: number;
  faction_loyalty_npc: FactionLoyaltyNpcDefinition;
  submitting: boolean;
  error: string | null;
  assistance_message: string | null;
  on_assist: () => void;
  on_stop_assisting: () => void;
  on_view_npc_details: () => void;
}
