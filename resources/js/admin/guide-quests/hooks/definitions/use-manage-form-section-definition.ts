import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseManageFormSectionDefinition {
  convertObjectToKeyValue: (object: {
    [key: string | number]: string;
  }) => DropdownItem[];
  handleUpdateFormData: (key: string, value: DropdownItem | string) => void;
  convertArrayToDropDown: (data: string[]) => DropdownItem[];
  getPreSelected: (
    items: DropdownItem[],
    candidate: string | number | null
  ) => DropdownItem | undefined;
}
