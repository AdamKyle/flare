import { ReactNode } from 'react';

import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

export default interface InventoryItemDetailStackProps {
  slot_id: number;
  character_id: number;
  aria_label: string;
  on_close: () => void;
  on_action: (successMessage: string) => void;
  show_actions?: boolean;
  footer_options?: SidePeekOptionDefinition[];
  notice?: ReactNode;
}
