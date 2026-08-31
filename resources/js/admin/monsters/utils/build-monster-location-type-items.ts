import {
  LOCATION_TYPE_LABELS,
  LOCATION_TYPE_VALUES,
} from '../../locations/enums/location-type';
import {
  MONSTER_LIST_WEEKLY_FIGHT_LOCATION_TYPES,
  MonsterListCategory,
} from '../enums/monster-list-category';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

/**
 * Location Type options for the Monster list filter, scoped to the
 * Location Types relevant to the selected category: Special Location allows
 * any Location Type, Weekly Fight is restricted to its fixed subset.
 */
export const buildMonsterLocationTypeItems = (
  category: MonsterListCategory
): DropdownItem[] => {
  const locationTypes =
    category === MonsterListCategory.WEEKLY_FIGHT
      ? MONSTER_LIST_WEEKLY_FIGHT_LOCATION_TYPES
      : LOCATION_TYPE_VALUES;

  return locationTypes.map((locationType) => ({
    value: locationType,
    label: LOCATION_TYPE_LABELS[locationType],
  }));
};
