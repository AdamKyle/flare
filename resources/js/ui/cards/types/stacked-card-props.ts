import { ReactNode } from 'react';

export default interface StackedCardProps {
  children: ReactNode;
  on_close: () => void;
  aria_label?: string;
}
