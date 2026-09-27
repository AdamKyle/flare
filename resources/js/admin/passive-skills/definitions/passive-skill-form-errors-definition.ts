import PassiveSkillFormStateDefinition from './passive-skill-form-state-definition';

type PassiveSkillFormErrorsDefinition = Partial<
  Record<keyof PassiveSkillFormStateDefinition, string>
>;

export default PassiveSkillFormErrorsDefinition;
