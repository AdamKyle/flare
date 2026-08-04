import GemCraftingApiResponseDefinition from '../../definitions/gem-crafting-api-response-definition';

export default interface UseGemCraftingApiDefinition {
  data: GemCraftingApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  replaceData: (data: GemCraftingApiResponseDefinition) => void;
}
