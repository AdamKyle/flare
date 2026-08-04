import AlchemyApiResponseDefinition from '../../definitions/alchemy-api-response-definition';

export default interface UseAlchemyApiDefinition {
  data: AlchemyApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  replaceData: (data: AlchemyApiResponseDefinition) => void;
}
