import EnchantingApiResponseDefinition from '../../definitions/enchanting-api-response-definition';
export default interface UseEnchantingApiDefinition {
  data: EnchantingApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  replaceData: (data: EnchantingApiResponseDefinition) => void;
}
