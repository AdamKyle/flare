import EnchantingApiResponseDefinition from '../../definitions/enchanting-api-response-definition';
export default interface UseEnchantItemApiDefinition {
  submitting: boolean;
  error: string | null;
  enchant: () => Promise<EnchantingApiResponseDefinition | null>;
}
