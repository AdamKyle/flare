import { ReactNode } from 'react';

export default interface CardProps {
  children: ReactNode | ReactNode[];
  additional_css?: string;
}
