import MonsterFormStateDefinition from './monster-form-state-definition';

type MonsterFormErrorsDefinition = Partial<
  Record<keyof MonsterFormStateDefinition, string>
>;

export default MonsterFormErrorsDefinition;
