export default interface MarkDownEditorProps {
  id?: string;
  placeholder?: string;
  on_value_change?: (markdown: string) => void;
  class_name?: string;
  initial_markdown?: string | null;
}
