import { ReactNode } from 'react';

export default interface StepProps {
  step_title: string;
  show_title?: boolean;
  children?: ReactNode;
}
