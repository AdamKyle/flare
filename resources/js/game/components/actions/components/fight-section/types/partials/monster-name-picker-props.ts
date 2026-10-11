import MonsterNameListDefinition from '../../deffinitions/monster-name-list-definition';

export default interface MonsterNamePickerProps {
  display_name: string;
  monsters: MonsterNameListDefinition[];
  current_index: number;
  on_select: (index: number) => void;
  disabled?: boolean;
  data_guide_target?: string;
  data_guide_target_complete?: boolean;
}
