import { ReactNode } from 'react';

export default interface ProgressInfoScreenLayoutProps {
  title: string;
  on_close: () => void;
  children: ReactNode;
}
