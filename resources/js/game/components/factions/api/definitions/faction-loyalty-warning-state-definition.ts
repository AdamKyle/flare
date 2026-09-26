import FactionLoyaltyWarningNoticeDefinition from './faction-loyalty-warning-notice-definition';

export default interface FactionLoyaltyWarningStateDefinition {
  has_warning: boolean;
  warning_notices: FactionLoyaltyWarningNoticeDefinition[];
  warning_notice: FactionLoyaltyWarningNoticeDefinition | null;
}
