import ClassMasteryDetailDefinition from '../../../../../reusable-components/class-mastery/types/class-mastery-detail-definition';

export interface ClassSpecialtyDefinition {
  id: number;
  game_class_id: number;
  class_name: string;
  name: string;
  description: string | null;
  requires_class_rank_level: number;
  class_mastery: ClassMasteryDetailDefinition;
}

export interface CharacterClassSpecialtyProgressDefinition {
  id: number;
  character_id: number;
  game_class_special_id: number;
  class_name: string;
  level: number;
  current_xp: number;
  required_xp: number;
  equipped: boolean;
  specialty_damage: number;
  is_mastered: boolean;
  class_mastery: ClassMasteryDetailDefinition;
}
