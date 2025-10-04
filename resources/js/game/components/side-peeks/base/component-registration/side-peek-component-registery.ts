import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BackPack from '../../character-inventory/backpack/backpack';
import GemBag from '../../character-inventory/gem-bag/gem-bag';
import GemBagProps from '../../character-inventory/gem-bag/types/gem-bag-props';
import Sets from '../../character-inventory/sets/sets';
import SetsProps from '../../character-inventory/sets/types/sets-props';
import UsableItemsProps from '../../character-inventory/usable-items/types/usable-items-props';
import UsableItems from '../../character-inventory/usable-items/usable-items';
import CharacterKingdomDetails from '../../map-actions/kingdom-details/character-kingdom-details';
import CharacterKingdomDetailsProps from '../../map-actions/kingdom-details/types/character-kingdom-details-props';
import LocationDetails from '../../map-actions/location-details/location-details';
import LocationDetailsProps from '../../map-actions/location-details/types/location-details-props';
import Teleport from '../../map-actions/teleport/teleport';
import TeleportProps from '../../map-actions/teleport/types/teleport-props';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export const SidePeekComponentRegistry = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: {
    component: BackPack,
    props: {} as SidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.GEM_BAG]: {
    component: GemBag,
    props: {} as GemBagProps,
  },
  [SidePeekComponentRegistrationEnum.USABLE_ITEMS]: {
    component: UsableItems,
    props: {} as UsableItemsProps,
  },
  [SidePeekComponentRegistrationEnum.SETS]: {
    component: Sets,
    props: {} as SetsProps,
  },
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_TELEPORT]: {
    component: Teleport,
    props: {} as TeleportProps,
  },
  [SidePeekComponentRegistrationEnum.LOCATION_DETAILS]: {
    component: LocationDetails,
    props: {} as LocationDetailsProps,
  },
  [SidePeekComponentRegistrationEnum.CHARACTER_KINGDOM_DETAILS]: {
    component: CharacterKingdomDetails,
    props: {} as CharacterKingdomDetailsProps,
  },
  // Add more components here
};
