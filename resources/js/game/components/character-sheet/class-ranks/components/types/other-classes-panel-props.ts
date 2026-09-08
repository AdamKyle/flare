import ClassRankDefinition from '../../api/definitions/class-rank-definition';

export default interface OtherClassesPanelProps {
  other_classes: ClassRankDefinition[];
  on_open_class: (gameClassId: number) => void;
}
