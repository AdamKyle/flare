import DropDownDataDefinition from '../definitions/drop-down-data-definition';

export default interface DropDownProps<T> {
  data: DropDownDataDefinition<T>;
  on_select: (value: T) => void;
  disabled?: boolean;
}
