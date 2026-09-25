import { ReactNode } from 'react';

import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

export default interface InventoryStackBodyProps {
  children: ReactNode;
  footer_options?: SidePeekOptionDefinition[];
}
