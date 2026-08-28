import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { ReactNode } from 'react';

export default interface FormWizardProps {
  total_steps: number;
  name?: string;
  is_loading?: boolean;
  render_loading_icon?: () => ReactNode;
  on_request_next?: (current_index: number) => Promise<boolean> | boolean;
  finish_label?: string;
  children: ReactNode;
  form_error: AxiosErrorDefinition | null;
  embedded?: boolean;
  icon_navigation?: boolean;
  current_step_index?: number;
  on_step_change?: (current_index: number) => void;
}
