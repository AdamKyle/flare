import { ReactNode } from 'react';

export default interface FloatingCardProps {
  title: string;
  close_action: () => void;
  back_action?: () => void;
  children: ReactNode | ReactNode[];
}
