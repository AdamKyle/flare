import { ReactNode } from 'react';

export default interface DetailGridProps {
  children: ReactNode;
  additional_css?: string;
  single_column?: boolean;
}
