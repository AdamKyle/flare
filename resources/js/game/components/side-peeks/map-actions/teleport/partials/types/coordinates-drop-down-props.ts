import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface CoordinatesDropDownProps {
  coordinates: number[];
  default_position: DropdownItem;
  on_select: (selectedItem: DropdownItem) => void;
  on_clear: () => void;
}
