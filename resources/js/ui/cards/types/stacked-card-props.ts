import { ReactNode } from 'react';

import { StackedCardContentMode } from '../enums/stacked-card-content-mode';

export default interface StackedCardProps {
  children: ReactNode;
  on_close: () => void;
  back_action?: () => void;
  aria_label?: string;
  content_mode?: StackedCardContentMode;
  close_button_data_guide_target?: string;
}
