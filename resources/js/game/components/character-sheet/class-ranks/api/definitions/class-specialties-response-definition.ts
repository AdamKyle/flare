import {
  CharacterClassSpecialtyProgressDefinition,
  ClassSpecialtyDefinition,
} from './class-specialty-definition';

export interface ClassSpecialtiesClassRankDefinition {
  game_class_id: number;
  level: number;
}

export default interface ClassSpecialtiesResponseDefinition {
  class_specialties: ClassSpecialtyDefinition[];
  specials_equipped: CharacterClassSpecialtyProgressDefinition[];
  class_ranks: ClassSpecialtiesClassRankDefinition[];
  other_class_specials: CharacterClassSpecialtyProgressDefinition[];
  message?: string;
}
