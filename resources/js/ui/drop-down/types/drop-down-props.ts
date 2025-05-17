import React from 'react';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface DropdownProps {
  items: DropdownItem[];
  on_select: (item: DropdownItem) => void;
  on_clear?: () => void;
  is_in_modal?: boolean;
  all_click_outside?: boolean;
  use_pagination?: boolean;
  handle_scroll?: (e: React.UIEvent<HTMLDivElement>) => void;
  selection_placeholder?: string;
  additional_scroll_css?: string;
  pre_selected_item?: DropdownItem;
}
