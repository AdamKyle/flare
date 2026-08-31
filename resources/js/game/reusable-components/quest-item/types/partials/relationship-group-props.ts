import { ReactNode } from 'react';

export default interface RelationshipGroupProps {
  title: string;
  children: ReactNode;
  show_separator?: boolean;
  lead?: ReactNode;
}
