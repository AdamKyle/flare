export default interface NumberFieldProps {
  id: string;
  label: string;
  value: string;
  on_change: (value: string) => void;
  required?: boolean;
  description?: string;
  error?: string | null;
  disabled?: boolean;
  min?: number;
  max?: number;
  placeholder?: string;
}
