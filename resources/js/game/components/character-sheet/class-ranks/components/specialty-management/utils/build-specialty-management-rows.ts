import ClassRankDefinition from '../../../api/definitions/class-rank-definition';
import {
  CharacterClassSpecialtyProgressDefinition,
  ClassSpecialtyDefinition,
} from '../../../api/definitions/class-specialty-definition';
import SpecialtyManagementRowDefinition from '../types/specialty-management-row-definition';

export const buildSpecialtyManagementRows = (
  classSpecialties: ClassSpecialtyDefinition[],
  specialsEquipped: CharacterClassSpecialtyProgressDefinition[],
  otherClassSpecials: CharacterClassSpecialtyProgressDefinition[],
  classRanks: ClassRankDefinition[]
): SpecialtyManagementRowDefinition[] => {
  return classSpecialties.map((definition) => {
    const owningClassRank =
      classRanks.find(
        (classRank) => classRank.game_class_id === definition.game_class_id
      ) ?? null;

    const equippedProgress =
      specialsEquipped.find(
        (specialEquipped) =>
          specialEquipped.game_class_special_id === definition.id
      ) ?? null;

    const unequippedProgress =
      otherClassSpecials.find(
        (special) => special.game_class_special_id === definition.id
      ) ?? null;

    const progress = equippedProgress ?? unequippedProgress;

    const isAccessible =
      !!owningClassRank &&
      !owningClassRank.is_locked &&
      owningClassRank.level >= definition.requires_class_rank_level;

    const hasProgress = progress !== null;
    const isMastered = hasProgress && progress.is_mastered;
    const isInProgress = hasProgress && !progress.is_mastered;
    const isAvailable = !hasProgress && isAccessible;
    const isLockedLevel =
      !hasProgress &&
      !isAccessible &&
      !!owningClassRank &&
      !owningClassRank.is_locked;

    return {
      definition,
      owning_class_rank: owningClassRank,
      progress,
      is_equipped: equippedProgress !== null,
      is_damage: definition.class_mastery.type === 'attack',
      is_mastered: isMastered,
      is_in_progress: isInProgress,
      is_available: isAvailable,
      is_locked_level: isLockedLevel,
      is_accessible: isAccessible,
    };
  });
};
