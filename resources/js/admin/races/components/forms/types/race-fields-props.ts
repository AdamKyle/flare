import RaceFormErrorsDefinition from '../../../definitions/race-form-errors-definition';
import RaceFormStateDefinition from '../../../definitions/race-form-state-definition';

export default interface RaceFieldsProps {
  state: RaceFormStateDefinition;
  errors: RaceFormErrorsDefinition;
  on_change: <K extends keyof RaceFormStateDefinition>(
    field: K,
    value: RaceFormStateDefinition[K]
  ) => void;
}
