import type { ReactNode } from 'react';

export default interface CardBackProps {
  children: ReactNode;
  link_title: string;
  on_click_link: () => void;
}
