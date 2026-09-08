import ClassRankDefinition from '../api/definitions/class-rank-definition';
import { ClassRankProgressFilter } from '../enums/class-rank-progress-filter';

export const resolveClassRankProgressFilter = (
  classRank: ClassRankDefinition
): ClassRankProgressFilter => {
  if (classRank.is_mastered) {
    return ClassRankProgressFilter.MASTERED;
  }

  if (classRank.level > 0 || classRank.current_xp > 0) {
    return ClassRankProgressFilter.HAS_PROGRESS;
  }

  return ClassRankProgressFilter.NOT_MASTERED;
};
