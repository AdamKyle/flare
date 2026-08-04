import SeerCampApiResponseDefinition from '../../definitions/seer-camp-api-response-definition';
export default interface UseSeerActionApiDefinition {
  submitting: boolean;
  error: string | null;
  submit: () => Promise<SeerCampApiResponseDefinition | null>;
}
