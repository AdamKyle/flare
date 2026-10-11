import { ReactNode } from 'react';

export default interface FloatingCardProps {
  title: string;
  close_action: () => void;
  back_action?: () => void;
  close_button_data_guide_target?: string;
  children: ReactNode | ReactNode[];
}
