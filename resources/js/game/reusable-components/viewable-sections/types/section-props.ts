import { ReactNode } from 'react';

export default interface SectionProps {
  title: string;
  children: ReactNode;
  className?: string;
}
