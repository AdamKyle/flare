import ClassRankDefinition from '../../../api/definitions/class-rank-definition';
import {
  CharacterClassSpecialtyProgressDefinition,
  ClassSpecialtyDefinition,
} from '../../../api/definitions/class-specialty-definition';

export default interface SpecialtyManagementRowDefinition {
  definition: ClassSpecialtyDefinition;
  owning_class_rank: ClassRankDefinition | null;
  progress: CharacterClassSpecialtyProgressDefinition | null;
  is_equipped: boolean;
  is_damage: boolean;
  is_mastered: boolean;
  is_in_progress: boolean;
  is_available: boolean;
  is_locked_level: boolean;
  is_accessible: boolean;
}
