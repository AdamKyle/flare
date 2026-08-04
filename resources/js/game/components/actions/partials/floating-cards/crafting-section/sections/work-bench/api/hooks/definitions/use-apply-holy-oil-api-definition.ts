import WorkBenchApiResponseDefinition from '../../definitions/work-bench-api-response-definition';
export default interface UseApplyHolyOilApiDefinition {
  submitting: boolean;
  error: string | null;
  apply: () => Promise<WorkBenchApiResponseDefinition | null>;
}
