import FactionLoyaltyWarningNoticeDefinition from '../../api/definitions/faction-loyalty-warning-notice-definition';

export default interface FactionLoyaltyWarningsProps {
  warning_notices: FactionLoyaltyWarningNoticeDefinition[];
  dismissing: boolean;
  on_dismiss: (warningId: number) => void;
}
