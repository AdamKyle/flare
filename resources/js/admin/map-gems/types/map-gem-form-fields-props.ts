import MapGemFormOptionsDefinition from '../api/definitions/map-gem-form-options-definition';
import MapGemFormErrorsDefinition from '../definitions/map-gem-form-errors-definition';
import MapGemFormStateDefinition from '../definitions/map-gem-form-state-definition';

export default interface MapGemFormFieldsProps {
  state: MapGemFormStateDefinition;
  errors: MapGemFormErrorsDefinition;
  form_options: MapGemFormOptionsDefinition;
  on_change: <K extends keyof MapGemFormStateDefinition>(
    field: K,
    value: MapGemFormStateDefinition[K]
  ) => void;
}
