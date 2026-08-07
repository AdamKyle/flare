export default interface CraftingProgressActionButtonProps {
  idle_label: string;
  submitting_label: string;
  timeout_label: string;
  submitting: boolean;
  is_timeout_active: boolean;
  progress: number;
  formatted_remaining: string;
  disabled: boolean;
  on_click: () => void;
}
