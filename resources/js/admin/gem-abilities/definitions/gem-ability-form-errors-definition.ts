import GemAbilityFormStateDefinition from './gem-ability-form-state-definition';

type GemAbilityFormErrorsDefinition = Partial<
  Record<keyof GemAbilityFormStateDefinition, string>
>;

export default GemAbilityFormErrorsDefinition;
