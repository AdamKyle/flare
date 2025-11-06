import { ReactNode } from 'react';

export default interface FormWizardNavProps {
  current_index: number;
  total_steps: number;
  can_go_previous: boolean;
  is_last_step: boolean;
  is_loading?: boolean;
  on_previous_click: () => void;
  on_next_click: () => void;
  on_dot_click: (index_value: number) => void;
  render_loading_icon?: () => ReactNode;
}
