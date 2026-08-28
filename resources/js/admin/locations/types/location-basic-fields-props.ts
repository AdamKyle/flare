import LocationFormErrors from './location-form-errors';
import LocationFormState from './location-form-state';

export default interface LocationBasicFieldsProps {
  game_map_name: string;
  state: LocationFormState;
  errors: LocationFormErrors;
  coordinates: { x: number[]; y: number[] };
  on_change: <K extends keyof LocationFormState>(
    field: K,
    value: LocationFormState[K]
  ) => void;
}
