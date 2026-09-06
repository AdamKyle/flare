import LocationGemFormStateDefinition from './location-gem-form-state-definition';

type LocationGemFormErrorsDefinition = Partial<
  Record<keyof LocationGemFormStateDefinition, string>
>;

export default LocationGemFormErrorsDefinition;
