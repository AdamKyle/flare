import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const guideQuestDropdownItems = (
  options: Record<string | number, string>
): DropdownItem[] =>
  Object.entries(options).map(([value, label]) => ({ value, label }));

export const selectedGuideQuestOption = (
  items: DropdownItem[],
  value: string | number | null
): DropdownItem | undefined => {
  if (value === null) {
    return undefined;
  }

  return items.find((item) => String(item.value) === String(value));
};
