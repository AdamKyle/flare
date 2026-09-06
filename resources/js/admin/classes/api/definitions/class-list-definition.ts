import ClassRelatedIdentityDefinition from './class-related-identity-definition';

export default interface ClassListDefinition {
  id: number;
  name: string;
  damage_stat: string;
  to_hit_stat: string;
  has_unlock_requirements: boolean;
  primary_required_class: ClassRelatedIdentityDefinition | null;
  secondary_required_class: ClassRelatedIdentityDefinition | null;
  primary_required_class_level: number | null;
  secondary_required_class_level: number | null;
}
