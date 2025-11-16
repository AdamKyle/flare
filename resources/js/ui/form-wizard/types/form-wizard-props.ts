import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { ReactNode } from 'react';

export default interface FormWizardProps {
  total_steps: number;
  name?: string;
  is_loading?: boolean;
  render_loading_icon?: () => ReactNode;
  on_request_next?: (current_index: number) => Promise<boolean> | boolean;
  children: ReactNode;
  form_error: AxiosErrorDefinition | null;
}
