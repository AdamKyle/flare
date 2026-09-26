import PledgeResponseDefinition from '../../definitions/pledge-response-definition';

export default interface UseFactionPledgeDefinition {
  submitting: boolean;
  error: string | null;
  pledge: (factionId: number) => Promise<PledgeResponseDefinition | null>;
  remove_pledge: (
    factionId: number
  ) => Promise<PledgeResponseDefinition | null>;
}
