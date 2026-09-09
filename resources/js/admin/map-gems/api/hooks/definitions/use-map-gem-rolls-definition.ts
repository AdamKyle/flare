import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import AdminRolledGemDefinition from '../../../../shared/gems/api/definitions/admin-rolled-gem-definition';

export default interface UseMapGemRollsDefinition {
  rolls: AdminRolledGemDefinition[];
  loading: boolean;
  is_loading_more: boolean;
  error: AxiosErrorDefinition | null;
  has_more: boolean;
  load_next: () => void;
  refresh: () => void;
}
