import { ReactNode } from 'react';

export default interface SidePeekProps {
  is_open: boolean;
  on_close: () => void;
  allow_clicking_outside: boolean;
  children?: ReactNode;
}
