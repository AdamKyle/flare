import { DelveStatusDefinition } from '../../definitions/delve-status-definition';

export default interface UseDelveStatusDefinition {
  status: DelveStatusDefinition | null;
  loading: boolean;
  error: string | null;
  live_update_count: number;
  refetch: () => void;
}
