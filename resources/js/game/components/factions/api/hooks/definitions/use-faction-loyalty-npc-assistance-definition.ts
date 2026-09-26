import AssistNpcResponseDefinition from '../../definitions/assist-npc-response-definition';

export default interface UseFactionLoyaltyNpcAssistanceDefinition {
  submitting: boolean;
  error: string | null;
  assist: (
    factionLoyaltyNpcId: number
  ) => Promise<AssistNpcResponseDefinition | null>;
  stop_assisting: (
    factionLoyaltyNpcId: number
  ) => Promise<AssistNpcResponseDefinition | null>;
}
