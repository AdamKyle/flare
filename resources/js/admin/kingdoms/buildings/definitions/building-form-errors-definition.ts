import BuildingFormStateDefinition from './building-form-state-definition';

type BuildingFormErrorsDefinition = Partial<
  Record<keyof BuildingFormStateDefinition, string>
>;

export default BuildingFormErrorsDefinition;
