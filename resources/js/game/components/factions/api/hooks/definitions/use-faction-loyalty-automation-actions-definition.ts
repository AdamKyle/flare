import FactionLoyaltyWarningStateDefinition from '../../definitions/faction-loyalty-warning-state-definition';
import FactionMessageResponseDefinition from '../../definitions/faction-message-response-definition';

export default interface UseFactionLoyaltyAutomationActionsDefinition {
  submitting: boolean;
  error: string | null;
  start: (
    attackType: string
  ) => Promise<FactionMessageResponseDefinition | null>;
  stop: () => Promise<FactionMessageResponseDefinition | null>;
  dismiss_warning: (
    warningId: number
  ) => Promise<FactionLoyaltyWarningStateDefinition | null>;
}
