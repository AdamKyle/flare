import FactionLoyaltyWarningNoticeDefinition from '../../definitions/faction-loyalty-warning-notice-definition';

export default interface UseFactionLoyaltyStatusParams {
  character_id: number;
  user_id: number;
  is_pledged: boolean;
  initial_warning_notices: FactionLoyaltyWarningNoticeDefinition[];
}
