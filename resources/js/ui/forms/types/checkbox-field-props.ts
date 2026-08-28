export default interface CheckboxFieldProps {
  id: string;
  label: string;
  checked: boolean;
  on_change: (checked: boolean) => void;
  description?: string;
  error?: string | null;
  disabled?: boolean;
}
