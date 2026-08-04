import {
  ArmourTypeOptionDefinition,
  CraftTypeOptionDefinition,
} from '../definitions/craft-type-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface CraftTypeFiltersProps {
  selectedType: string | null;
  armourType: string | null;
  selectedTypeOption: CraftTypeOptionDefinition | undefined;
  selectedArmourTypeOption: ArmourTypeOptionDefinition | undefined;
  onTypeChange: (item: DropdownItem) => void;
  onArmourTypeChange: (item: DropdownItem) => void;
}
