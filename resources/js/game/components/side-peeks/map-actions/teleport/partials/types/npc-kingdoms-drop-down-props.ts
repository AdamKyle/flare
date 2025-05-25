import CharacterKingdomsPositionDefinitions from '../../../../../../api-definitions/map-details/character-kingdoms-position-definitions';
import { LocationTypes } from '../../enums/location-types';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface NpcKingdomsDropDownProps {
  npc_kingdoms: CharacterKingdomsPositionDefinitions[];
  location_type_selected: LocationTypes | null;
  on_select: (item: DropdownItem, locationType: LocationTypes) => void;
}
