import ClassFormDefinition from '../api/definitions/class-form-definition';

export default interface ClassFormContentProps {
  class_id: number | null;
  on_saved: (game_class: ClassFormDefinition) => void;
  on_cancel: () => void;
  embedded: boolean;
}
