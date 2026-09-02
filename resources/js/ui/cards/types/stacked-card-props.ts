import { ReactNode } from 'react';

import { StackedCardContentMode } from '../enums/stacked-card-content-mode';

export default interface StackedCardProps {
  children: ReactNode;
  on_close: () => void;
  aria_label?: string;
  content_mode?: StackedCardContentMode;
}
