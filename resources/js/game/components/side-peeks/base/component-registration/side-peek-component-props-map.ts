import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BugReportSidePeekProps from '../../../../../admin/logs-dashboard/components/side-peeks/types/bug-report-side-peek-props';
import LogEntrySidePeekProps from '../../../../../admin/logs-dashboard/components/side-peeks/types/log-entry-side-peek-props';
import BackpackProps from '../../character-inventory/backpack/types/backpack-props';
import GemBagProps from '../../character-inventory/gem-bag/types/gem-bag-props';
import SetsProps from '../../character-inventory/sets/types/sets-props';
import UsableItemsProps from '../../character-inventory/usable-items/types/usable-items-props';
import CraftedItemProps from '../../crafted-item/types/crafted-item-props';
import ItemDetailsProps from '../../item-details/types/item-details-props';
import ConjureProps from '../../map-actions/conjure/types/conjure-props';
import CharacterKingdomDetailsProps from '../../map-actions/kingdom-details/types/character-kingdom-details-props';
import LocationDetailsProps from '../../map-actions/location-details/types/location-details-props';
import SetSailProps from '../../map-actions/set-sail/types/set-sail-props';
import TeleportProps from '../../map-actions/teleport/types/teleport-props';
import TraversePropsDefinition from '../../map-actions/traverse/definitions/traverse-props-definition';
import ServerChatItemProps from '../../server-chat-item/types/server-chat-item-props';

export type SidePeekComponentPropsMap = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: BackpackProps;
  [SidePeekComponentRegistrationEnum.GEM_BAG]: GemBagProps;
  [SidePeekComponentRegistrationEnum.USABLE_ITEMS]: UsableItemsProps;
  [SidePeekComponentRegistrationEnum.SETS]: SetsProps;
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_TELEPORT]: TeleportProps;
  [SidePeekComponentRegistrationEnum.LOCATION_DETAILS]: LocationDetailsProps;
  [SidePeekComponentRegistrationEnum.CHARACTER_KINGDOM_DETAILS]: CharacterKingdomDetailsProps;
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_TRAVERSE]: TraversePropsDefinition;
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_SET_SAIL]: SetSailProps;
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_CONJURE]: ConjureProps;
  [SidePeekComponentRegistrationEnum.SERVER_CHAT_ITEM]: ServerChatItemProps;
  [SidePeekComponentRegistrationEnum.CRAFTED_ITEM]: CraftedItemProps;
  [SidePeekComponentRegistrationEnum.ITEM_DETAILS]: ItemDetailsProps;
  [SidePeekComponentRegistrationEnum.ADMIN_LOG_ENTRY]: LogEntrySidePeekProps;
  [SidePeekComponentRegistrationEnum.ADMIN_BUG_REPORT]: BugReportSidePeekProps;
  // Future components go here
};
