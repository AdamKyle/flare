import ClassRankDefinition from '../api/definitions/class-rank-definition';
import { ClassRankVisualState } from '../enums/class-rank-visual-state';

export const resolveClassRankVisualState = (
  classRank: ClassRankDefinition
): ClassRankVisualState => {
  if (classRank.is_active) {
    return ClassRankVisualState.CURRENT;
  }

  if (classRank.is_locked) {
    return ClassRankVisualState.LOCKED;
  }

  if (classRank.is_mastered) {
    return ClassRankVisualState.MASTERED;
  }

  return ClassRankVisualState.UNLOCKED;
};
