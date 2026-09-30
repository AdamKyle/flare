import { ReactNode } from 'react';

export default interface SectionProps {
  title: ReactNode;
  children: ReactNode;
  className?: string;
  showSeparator?: boolean;
  showTitleSeparator?: boolean;
  lead?: ReactNode;
}
