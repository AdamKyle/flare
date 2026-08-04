import LabyrinthOracleApiResponseDefinition from '../../definitions/labyrinth-oracle-api-response-definition';
export default interface UseTransferItemAttributesApiDefinition {
  submitting: boolean;
  error: string | null;
  transfer: () => Promise<LabyrinthOracleApiResponseDefinition | null>;
}
