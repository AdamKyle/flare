import UnitFormStateDefinition from './unit-form-state-definition';

type UnitFormErrorsDefinition = Partial<
  Record<keyof UnitFormStateDefinition, string>
>;

export default UnitFormErrorsDefinition;
