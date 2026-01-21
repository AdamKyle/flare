import DropDownItemDefinition from "ui/buttons/definitions/drop-down-button-item-definition";


export default interface DropDownDataDefinition<T> {
  dropdown_label: string;
  items: DropDownItemDefinition<T>[];
}
