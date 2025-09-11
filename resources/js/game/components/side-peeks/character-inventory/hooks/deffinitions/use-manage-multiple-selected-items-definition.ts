import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseManageMultipleSelectedItemsApiParams from './use-manage-multiple-selected-items-api-params';

export default interface UseManageMultipleSelectedItemsDefinition {
  successMessage: string | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
  handleSelection: (params: UseManageMultipleSelectedItemsApiParams) => void;
}
