import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BackPackProps from '../../character-inventory/backpack/types/backpack-props';
import GemBagProps from '../../character-inventory/gem-bag/types/gem-bag-props';
import SetsProps from '../../character-inventory/sets/types/sets-props';
import UsableItemsProps from '../../character-inventory/usable-items/types/usable-items-props';
import CharacterKingdomDetailsProps from '../../map-actions/kingdom-details/types/character-kingdom-details-props';
import LocationDetailsProps from '../../map-actions/location-details/types/location-details-props';
import TeleportProps from '../../map-actions/teleport/types/teleport-props';

export type SidePeekComponentPropsMap = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: BackPackProps;
  [SidePeekComponentRegistrationEnum.GEM_BAG]: GemBagProps;
  [SidePeekComponentRegistrationEnum.USABLE_ITEMS]: UsableItemsProps;
  [SidePeekComponentRegistrationEnum.SETS]: SetsProps;
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_TELEPORT]: TeleportProps;
  [SidePeekComponentRegistrationEnum.LOCATION_DETAILS]: LocationDetailsProps;
  [SidePeekComponentRegistrationEnum.CHARACTER_KINGDOM_DETAILS]: CharacterKingdomDetailsProps;
  // Future components go here
};
