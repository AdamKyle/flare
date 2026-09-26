import {
  DelveActiveStatusDefinition,
  DelveCompletedStatusDefinition,
} from '../../api/definitions/delve-status-definition';

export default interface DelveStatusPanelProps {
  character_id: number;
  status: DelveActiveStatusDefinition | DelveCompletedStatusDefinition;
  on_refetch: () => void;
}
