import PassiveSkillEffectOptionDefinition from './passive-skill-effect-option-definition';
import PassiveSkillRelatedIdentityDefinition from './passive-skill-related-identity-definition';

export default interface PassiveSkillFormOptionsDefinition {
  effects: PassiveSkillEffectOptionDefinition[];
  passive_skills: PassiveSkillRelatedIdentityDefinition[];
}
