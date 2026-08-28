export default interface TextAreaFieldProps {
  id: string;
  label: string;
  value: string;
  on_change: (value: string) => void;
  required?: boolean;
  description?: string;
  error?: string | null;
  disabled?: boolean;
  rows?: number;
  placeholder?: string;
}
