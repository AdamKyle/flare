import SkillFormDefinition from './skill-form-definition';
import SkillRelatedIdentityDefinition from './skill-related-identity-definition';

export default interface SkillDetailDefinition extends SkillFormDefinition {
  game_class: SkillRelatedIdentityDefinition | null;
}
