export default interface OnboardingNavigationProps {
  step: number;
  total: number;
  is_first: boolean;
  is_last: boolean;
  submitting: boolean;
  submit_error: string | null;
  on_back: () => void;
  on_next: () => void;
  on_finish: () => void;
}
