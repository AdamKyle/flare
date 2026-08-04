import GemCraftingApiResponseDefinition from '../../definitions/gem-crafting-api-response-definition';

export default interface UseCraftGemApiDefinition {
  crafting: boolean;
  error: string | null;
  craft: (tier: number) => Promise<GemCraftingApiResponseDefinition | null>;
}
