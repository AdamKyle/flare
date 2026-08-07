import { ReactNode } from 'react';

export default interface CraftingActionLayoutProps {
  title: string;
  status?: ReactNode;
  progress?: ReactNode;
  form: ReactNode;
  preview?: ReactNode;
  action?: ReactNode;
  help_href?: string;
  help_label?: string;
}
