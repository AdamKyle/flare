import RaceFormStateDefinition from './race-form-state-definition';

type RaceFormErrorsDefinition = Partial<
  Record<keyof RaceFormStateDefinition, string>
>;

export default RaceFormErrorsDefinition;
