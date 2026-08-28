import LocationFormOptionsDefinition from '../api/definitions/location-form-options-definition';
import LocationFormErrors from './location-form-errors';
import LocationFormState from './location-form-state';

export default interface LocationRulesFieldsProps {
  state: LocationFormState;
  errors: LocationFormErrors;
  form_options: LocationFormOptionsDefinition;
  on_change: <K extends keyof LocationFormState>(
    field: K,
    value: LocationFormState[K]
  ) => void;
}
