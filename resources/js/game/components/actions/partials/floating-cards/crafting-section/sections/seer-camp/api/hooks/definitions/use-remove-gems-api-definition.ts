import RemoveAllGemsApiResponseDefinition from '../../definitions/remove-all-gems-api-response-definition';
export default interface UseRemoveGemsApiDefinition {
  submitting: boolean;
  error: string | null;
  removeAll: () => Promise<RemoveAllGemsApiResponseDefinition | null>;
}
