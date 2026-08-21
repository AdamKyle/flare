import React from 'react';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface DropdownProps {
  id?: string;
  aria_label?: string;
  aria_labelled_by?: string;
  items: DropdownItem[];
  on_select: (item: DropdownItem) => void;
  on_clear?: () => void;
  selection_placeholder?: string;
  pre_selected_item?: DropdownItem;
  force_clear?: boolean;
  disabled?: boolean;
  focus_selected_on_open?: boolean;
  header_slot?: React.ReactNode;
  searchable?: boolean;
  search_value?: string;
  on_search?: (value: string) => void;
  can_load_more?: boolean;
  is_loading_more?: boolean;
  on_end_reached?: () => void;
  empty_message?: string;
  search_placeholder?: string;
  on_open?: () => void;
}
