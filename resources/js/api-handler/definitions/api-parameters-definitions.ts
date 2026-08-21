export default interface ApiParametersDefinitions<
  F extends Record<string, unknown> = Record<string, unknown>,
> {
  url: string;
  urlParams?: Record<string, number>;
  enabled?: boolean;
  additionalParams?: Record<string, unknown>;
  initialSearchText?: string;
  initialFilters?: Partial<F>;
}
