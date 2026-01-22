import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface SetChoicesProps {
  character_id: number;
  on_set_change: (selectedSet: DropdownItem) => void;
  on_set_selection_clear: () => void;
  set_equipped_set_name?: boolean;
  dont_show_equipped_set?: boolean;
}
