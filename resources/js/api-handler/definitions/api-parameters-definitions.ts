export default interface ApiParametersDefinitions {
  url: string;
  urlParams?: Record<string, number>;
  enabled?: boolean;
  additionalParams?: Record<string, unknown>;
}
