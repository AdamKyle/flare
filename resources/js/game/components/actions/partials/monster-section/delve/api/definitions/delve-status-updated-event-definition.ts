import { DelveStatusDefinition } from './delve-status-definition';

export default interface DelveStatusUpdatedEventDefinition {
  user_id: number;
  status: DelveStatusDefinition;
}
