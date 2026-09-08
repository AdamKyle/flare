import { LOCATION_TYPE_LABELS } from '../../locations/enums/location-type';
import { MONSTER_LIST_WEEKLY_FIGHT_LOCATION_TYPES } from '../enums/monster-list-category';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const buildMonsterLocationTypeItems = (): DropdownItem[] => {
  return MONSTER_LIST_WEEKLY_FIGHT_LOCATION_TYPES.map((locationType) => ({
    value: locationType,
    label: LOCATION_TYPE_LABELS[locationType],
  }));
};
