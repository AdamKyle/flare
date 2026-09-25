import AreaGemSourceDefinition from '../api/definitions/area-gem-source-definition';
import {
  LOCATION_GEM_PLAYER_DISPLAY_GROUPS,
  MAP_GEM_PLAYER_DISPLAY_GROUPS,
} from '../definitions/player-rolled-gem-display-groups';
import { RolledGemDisplayGroup } from '../types/rolled-gem-display-group';

export const resolveRolledGemDisplayGroups = (
  source: AreaGemSourceDefinition
): RolledGemDisplayGroup[] =>
  source.type === 'map_gem'
    ? MAP_GEM_PLAYER_DISPLAY_GROUPS
    : LOCATION_GEM_PLAYER_DISPLAY_GROUPS;
