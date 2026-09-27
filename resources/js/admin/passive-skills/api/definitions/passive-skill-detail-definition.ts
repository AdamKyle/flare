import PassiveSkillChildDefinition from './passive-skill-child-definition';
import PassiveSkillFormDefinition from './passive-skill-form-definition';
import PassiveSkillRelatedIdentityDefinition from './passive-skill-related-identity-definition';

export default interface PassiveSkillDetailDefinition extends PassiveSkillFormDefinition {
  parent: PassiveSkillRelatedIdentityDefinition | null;
  child_skills: PassiveSkillChildDefinition[];
}
