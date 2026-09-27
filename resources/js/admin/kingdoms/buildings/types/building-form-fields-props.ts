import BuildingFormOptionsDefinition from '../api/definitions/building-form-options-definition';
import BuildingFormErrorsDefinition from '../definitions/building-form-errors-definition';
import BuildingFormStateDefinition from '../definitions/building-form-state-definition';

export default interface BuildingFormFieldsProps {
  state: BuildingFormStateDefinition;
  errors: BuildingFormErrorsDefinition;
  form_options: BuildingFormOptionsDefinition;
  on_change: <K extends keyof BuildingFormStateDefinition>(
    field: K,
    value: BuildingFormStateDefinition[K]
  ) => void;
}
