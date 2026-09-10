import ClassDetailDefinition from './class-detail-definition';

export default interface ClassDetailProps {
  game_class: ClassDetailDefinition;
  on_open_class?: (classId: number) => void;
  single_column?: boolean;
}
