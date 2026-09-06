import LocationGemFormOptionsDefinition from '../api/definitions/location-gem-form-options-definition';
import LocationGemFormErrorsDefinition from '../definitions/location-gem-form-errors-definition';
import LocationGemFormStateDefinition from '../definitions/location-gem-form-state-definition';

export default interface LocationGemFormFieldsProps {
  state: LocationGemFormStateDefinition;
  errors: LocationGemFormErrorsDefinition;
  form_options: LocationGemFormOptionsDefinition;
  on_change: <K extends keyof LocationGemFormStateDefinition>(
    field: K,
    value: LocationGemFormStateDefinition[K]
  ) => void;
}
