import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import GemBagProps from '../../character-inventory/gem-bag/types/gem-bag-props';
import SetsProps from '../../character-inventory/sets/types/sets-props';
import UsableItemsProps from '../../character-inventory/usable-items/types/usable-items-props';
import CharacterKingdomDetailsProps from '../../map-actions/kingdom-details/types/character-kingdom-details-props';
import LocationDetailsProps from '../../map-actions/location-details/types/location-details-props';
import TeleportProps from '../../map-actions/teleport/types/teleport-props';
import TraversePropsDefinition from '../../map-actions/traverse/definitions/traverse-props-definition';
import ServerChatItemProps from '../../server-chat-item/types/server-chat-item-props';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export type SidePeekComponentPropsMap = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: SidePeekProps;
  [SidePeekComponentRegistrationEnum.GEM_BAG]: GemBagProps;
  [SidePeekComponentRegistrationEnum.USABLE_ITEMS]: UsableItemsProps;
  [SidePeekComponentRegistrationEnum.SETS]: SetsProps;
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_TELEPORT]: TeleportProps;
  [SidePeekComponentRegistrationEnum.LOCATION_DETAILS]: LocationDetailsProps;
  [SidePeekComponentRegistrationEnum.CHARACTER_KINGDOM_DETAILS]: CharacterKingdomDetailsProps;
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_TRAVERSE]: TraversePropsDefinition;
  [SidePeekComponentRegistrationEnum.SERVER_CHAT_ITEM]: ServerChatItemProps;
  // Future components go here
};
