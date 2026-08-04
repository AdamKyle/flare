import LabyrinthOracleApiResponseDefinition from '../../definitions/labyrinth-oracle-api-response-definition';
export default interface UseLabyrinthOracleApiDefinition {
  data: LabyrinthOracleApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  replaceData: (data: LabyrinthOracleApiResponseDefinition) => void;
}
