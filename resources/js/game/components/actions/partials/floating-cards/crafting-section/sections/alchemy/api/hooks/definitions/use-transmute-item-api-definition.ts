import AlchemyApiResponseDefinition from '../../definitions/alchemy-api-response-definition';

export default interface UseTransmuteItemApiDefinition {
  transmuting: boolean;
  error: string | null;
  transmute: () => Promise<AlchemyApiResponseDefinition | null>;
}
