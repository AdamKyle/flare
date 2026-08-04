import TrinketryApiResponseDefinition from '../../definitions/trinketry-api-response-definition';

export default interface UseTrinketryApiDefinition {
  data: TrinketryApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  replaceData: (data: TrinketryApiResponseDefinition) => void;
}
