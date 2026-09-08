import ClassRankDefinition from '../../api/definitions/class-rank-definition';

export default interface OtherClassCardProps {
  class_rank: ClassRankDefinition;
  on_click: (gameClassId: number) => void;
}
