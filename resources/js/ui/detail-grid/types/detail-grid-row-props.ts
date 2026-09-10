import { ReactNode } from 'react';

export default interface DetailGridRowProps {
  children: ReactNode;
  additional_css?: string;
  single_column?: boolean;
}
