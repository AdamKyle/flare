import WeaponMasteryDefinition from './weapon-mastery-definition';
import ClassDetailDefinition from '../../../../../reusable-components/class/types/class-detail-definition';

export interface ClassRankUnlockRequirementDefinition {
  id: number;
  name: string;
  current_level: number;
  required_level: number;
  is_met: boolean;
}

export interface ClassRankUnlockProgressDefinition {
  primary: ClassRankUnlockRequirementDefinition;
  secondary: ClassRankUnlockRequirementDefinition;
}

export default interface ClassRankDefinition {
  id: number;
  game_class_id: number;
  class_name: string;
  level: number;
  current_xp: number;
  required_xp: number;
  is_active: boolean;
  is_locked: boolean;
  is_mastered: boolean;
  class_detail: ClassDetailDefinition;
  unlock_progress: ClassRankUnlockProgressDefinition | null;
  weapon_masteries: WeaponMasteryDefinition[];
}
