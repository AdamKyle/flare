import { ReactNode } from 'react';

export default interface CraftingActionLayoutProps {
  heading: ReactNode;
  status?: ReactNode;
  progress?: ReactNode;
  form: ReactNode;
  preview?: ReactNode;
  result?: ReactNode;
  action?: ReactNode;
  help_link?: ReactNode;
}
