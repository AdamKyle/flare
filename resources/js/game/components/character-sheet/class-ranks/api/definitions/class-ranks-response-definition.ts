import ClassRankDefinition from './class-rank-definition';

export default interface ClassRanksResponseDefinition {
  class_ranks: ClassRankDefinition[];
  message?: string;
}
