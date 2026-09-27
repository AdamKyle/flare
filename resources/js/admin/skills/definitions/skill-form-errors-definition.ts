import SkillFormStateDefinition from './skill-form-state-definition';

type SkillFormErrorsDefinition = Partial<
  Record<keyof SkillFormStateDefinition, string>
>;

export default SkillFormErrorsDefinition;
