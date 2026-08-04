import TrinketryApiResponseDefinition from '../../definitions/trinketry-api-response-definition';

export default interface UseCraftTrinketApiDefinition {
  crafting: boolean;
  error: string | null;
  craft: () => Promise<TrinketryApiResponseDefinition | null>;
}
