import ClassMasteryFormDefinition from '../api/definitions/class-mastery-form-definition';

export default interface ClassMasteryFormContentProps {
  class_mastery_id: number | null;
  on_saved: (class_mastery: ClassMasteryFormDefinition) => void;
  on_cancel: () => void;
  embedded: boolean;
}
