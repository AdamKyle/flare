import FactionLoyaltyInfoDefinition from '../../definitions/faction-loyalty-info-definition';
import FactionLoyaltyWarningNoticeDefinition from '../../definitions/faction-loyalty-warning-notice-definition';
import FactionLoyaltyWarningStateDefinition from '../../definitions/faction-loyalty-warning-state-definition';

export default interface UseFactionLoyaltyStatusDefinition {
  info: FactionLoyaltyInfoDefinition | null;
  loading: boolean;
  error: string | null;
  warning_notices: FactionLoyaltyWarningNoticeDefinition[];
  live_update_count: number;
  refetch: () => void;
  apply_warning_state: (
    warningState: FactionLoyaltyWarningStateDefinition
  ) => void;
}
