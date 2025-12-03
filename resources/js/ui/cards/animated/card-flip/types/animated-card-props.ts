import type { ReactNode } from 'react';

export default interface AnimatedCardProps {
  aria_label: string;
  children: ReactNode;
  is_flipped?: boolean;
  on_click_card?: () => void;
}
