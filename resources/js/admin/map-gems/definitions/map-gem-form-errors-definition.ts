import MapGemFormStateDefinition from './map-gem-form-state-definition';

type MapGemFormErrorsDefinition = Partial<
  Record<keyof MapGemFormStateDefinition, string>
>;

export default MapGemFormErrorsDefinition;
