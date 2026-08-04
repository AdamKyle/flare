import SeerCampApiResponseDefinition from '../../definitions/seer-camp-api-response-definition';
export default interface UseSeerCampApiDefinition {
  data: SeerCampApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  removalLoading: boolean;
  removalError: string | null;
  replaceData: (data: SeerCampApiResponseDefinition) => void;
  fetchRemovalData: () => Promise<void>;
}
