import WorkBenchApiResponseDefinition from '../../definitions/work-bench-api-response-definition';
export default interface UseWorkBenchApiDefinition {
  data: WorkBenchApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  replaceData: (data: WorkBenchApiResponseDefinition) => void;
}
