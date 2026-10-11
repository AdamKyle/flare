import DropDownDataDefinition from '../definitions/drop-down-data-definition';

export default interface DropDownProps<T> {
  data: DropDownDataDefinition<T>;
  on_select: (value: T) => void;
  is_open?: boolean;
  on_open_change?: (isOpen: boolean) => void;
  disabled?: boolean;
}
