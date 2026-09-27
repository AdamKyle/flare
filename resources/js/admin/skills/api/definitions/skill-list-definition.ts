import SkillRelatedIdentityDefinition from './skill-related-identity-definition';

export default interface SkillListDefinition {
  id: number;
  name: string;
  type: number;
  max_level: number;
  can_train: boolean | null;
  is_locked: boolean;
  game_class: SkillRelatedIdentityDefinition | null;
}
