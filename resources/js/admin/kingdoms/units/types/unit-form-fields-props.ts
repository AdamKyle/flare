import UnitFormErrorsDefinition from '../definitions/unit-form-errors-definition';
import UnitFormStateDefinition from '../definitions/unit-form-state-definition';

export default interface UnitFormFieldsProps {
  state: UnitFormStateDefinition;
  errors: UnitFormErrorsDefinition;
  on_change: <K extends keyof UnitFormStateDefinition>(
    field: K,
    value: UnitFormStateDefinition[K]
  ) => void;
}
