import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseManageMovementDefinition {
  movementError: AxiosErrorDefinition | null;
}
