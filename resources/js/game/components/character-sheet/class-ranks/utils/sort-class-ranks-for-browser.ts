import { resolveClassRankProgressFilter } from './resolve-class-rank-progress-filter';
import ClassRankDefinition from '../api/definitions/class-rank-definition';
import { ClassRankProgressFilter } from '../enums/class-rank-progress-filter';

const groupOrder: Record<ClassRankProgressFilter, number> = {
  [ClassRankProgressFilter.MASTERED]: 0,
  [ClassRankProgressFilter.HAS_PROGRESS]: 1,
  [ClassRankProgressFilter.NOT_MASTERED]: 2,
  [ClassRankProgressFilter.ALL]: 3,
};

export const sortClassRanksForBrowser = (
  classRanks: ClassRankDefinition[]
): ClassRankDefinition[] => {
  return [...classRanks].sort((a, b) => {
    const groupComparison =
      groupOrder[resolveClassRankProgressFilter(a)] -
      groupOrder[resolveClassRankProgressFilter(b)];

    if (groupComparison !== 0) {
      return groupComparison;
    }

    const nameComparison = a.class_name.localeCompare(b.class_name);

    if (nameComparison !== 0) {
      return nameComparison;
    }

    return a.game_class_id - b.game_class_id;
  });
};
