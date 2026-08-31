import React from 'react';

import { SidePeekComponentPropsMap } from './side-peek-component-props-map';
import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import AdminGameMapDetailSidePeek from '../../../../../admin/game-maps/components/side-peeks/admin-game-map-detail-side-peek';
import GameMapCoordinateSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-coordinate-side-peek';
import GameMapFormSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-form-side-peek';
import GameMapImportSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-import-side-peek';
import GameMapKingdomSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-kingdom-side-peek';
import GameMapLocationSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-location-side-peek';
import GameMapNpcSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-npc-side-peek';
import GameMapRelatedLocationsSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-related-locations-side-peek';
import GameMapRelatedMonstersSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-related-monsters-side-peek';
import GameMapRelatedNpcsSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-related-npcs-side-peek';
import GameMapRelatedQuestItemsSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-related-quest-items-side-peek';
import GameMapRelatedQuestsSidePeek from '../../../../../admin/game-maps/components/side-peeks/game-map-related-quests-side-peek';
import AdminGameMapDetailSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/admin-game-map-detail-side-peek-props';
import GameMapCoordinateSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-coordinate-side-peek-props';
import GameMapFormSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-form-side-peek-props';
import GameMapImportSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-import-side-peek-props';
import GameMapKingdomSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-kingdom-side-peek-props';
import GameMapLocationSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-location-side-peek-props';
import GameMapNpcSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-npc-side-peek-props';
import GameMapRelatedLocationsSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-related-locations-side-peek-props';
import GameMapRelatedMonstersSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-related-monsters-side-peek-props';
import GameMapRelatedNpcsSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-related-npcs-side-peek-props';
import GameMapRelatedQuestItemsSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-related-quest-items-side-peek-props';
import GameMapRelatedQuestsSidePeekProps from '../../../../../admin/game-maps/components/side-peeks/types/game-map-related-quests-side-peek-props';
import AdminItemDetailSidePeek from '../../../../../admin/items/components/side-peeks/admin-item-detail-side-peek';
import ItemFormSidePeek from '../../../../../admin/items/components/side-peeks/item-form-side-peek';
import ItemImportSidePeek from '../../../../../admin/items/components/side-peeks/item-import-side-peek';
import AdminItemDetailSidePeekProps from '../../../../../admin/items/components/side-peeks/types/admin-item-detail-side-peek-props';
import ItemFormSidePeekProps from '../../../../../admin/items/components/side-peeks/types/item-form-side-peek-props';
import ItemImportSidePeekProps from '../../../../../admin/items/components/side-peeks/types/item-import-side-peek-props';
import AdminLocationDetailSidePeek from '../../../../../admin/locations/components/side-peeks/admin-location-detail-side-peek';
import LocationFormSidePeek from '../../../../../admin/locations/components/side-peeks/location-form-side-peek';
import LocationImportSidePeek from '../../../../../admin/locations/components/side-peeks/location-import-side-peek';
import AdminLocationDetailSidePeekProps from '../../../../../admin/locations/components/side-peeks/types/admin-location-detail-side-peek-props';
import LocationFormSidePeekProps from '../../../../../admin/locations/components/side-peeks/types/location-form-side-peek-props';
import LocationImportSidePeekProps from '../../../../../admin/locations/components/side-peeks/types/location-import-side-peek-props';
import BugReportSidePeek from '../../../../../admin/monitoring/logs-dashboard/components/side-peeks/bug-report-side-peek';
import LogEntrySidePeek from '../../../../../admin/monitoring/logs-dashboard/components/side-peeks/log-entry-side-peek';
import BugReportSidePeekProps from '../../../../../admin/monitoring/logs-dashboard/components/side-peeks/types/bug-report-side-peek-props';
import LogEntrySidePeekProps from '../../../../../admin/monitoring/logs-dashboard/components/side-peeks/types/log-entry-side-peek-props';
import AdminMonsterDetailSidePeek from '../../../../../admin/monsters/components/side-peeks/admin-monster-detail-side-peek';
import AdminMonsterDetailSidePeekProps from '../../../../../admin/monsters/components/side-peeks/types/admin-monster-detail-side-peek-props';
import AdminNpcDetailSidePeek from '../../../../../admin/npcs/components/side-peeks/admin-npc-detail-side-peek';
import NpcFormSidePeek from '../../../../../admin/npcs/components/side-peeks/npc-form-side-peek';
import NpcImportSidePeek from '../../../../../admin/npcs/components/side-peeks/npc-import-side-peek';
import AdminNpcDetailSidePeekProps from '../../../../../admin/npcs/components/side-peeks/types/admin-npc-detail-side-peek-props';
import NpcFormSidePeekProps from '../../../../../admin/npcs/components/side-peeks/types/npc-form-side-peek-props';
import NpcImportSidePeekProps from '../../../../../admin/npcs/components/side-peeks/types/npc-import-side-peek-props';
import AdminQuestDetailSidePeek from '../../../../../admin/quests/components/side-peeks/admin-quest-detail-side-peek';
import AdminQuestDetailSidePeekProps from '../../../../../admin/quests/components/side-peeks/types/admin-quest-detail-side-peek-props';
import BackPack from '../../character-inventory/backpack/backpack';
import BackpackProps from '../../character-inventory/backpack/types/backpack-props';
import GemBag from '../../character-inventory/gem-bag/gem-bag';
import GemBagProps from '../../character-inventory/gem-bag/types/gem-bag-props';
import Sets from '../../character-inventory/sets/sets';
import SetsProps from '../../character-inventory/sets/types/sets-props';
import UsableItemsProps from '../../character-inventory/usable-items/types/usable-items-props';
import UsableItems from '../../character-inventory/usable-items/usable-items';
import CraftedItem from '../../crafted-item/crafted-item';
import CraftedItemProps from '../../crafted-item/types/crafted-item-props';
import ItemDetails from '../../item-details/item-details';
import ItemDetailsProps from '../../item-details/types/item-details-props';
import Conjure from '../../map-actions/conjure/conjure';
import ConjureProps from '../../map-actions/conjure/types/conjure-props';
import CharacterKingdomDetails from '../../map-actions/kingdom-details/character-kingdom-details';
import CharacterKingdomDetailsProps from '../../map-actions/kingdom-details/types/character-kingdom-details-props';
import LocationDetails from '../../map-actions/location-details/location-details';
import LocationDetailsProps from '../../map-actions/location-details/types/location-details-props';
import SetSail from '../../map-actions/set-sail/set-sail';
import SetSailProps from '../../map-actions/set-sail/types/set-sail-props';
import Teleport from '../../map-actions/teleport/teleport';
import TeleportProps from '../../map-actions/teleport/types/teleport-props';
import TraversePropsDefinition from '../../map-actions/traverse/definitions/traverse-props-definition';
import Traverse from '../../map-actions/traverse/traverse';
import ServerChatItem from '../../server-chat-item/server-chat-item';
import ServerChatItemProps from '../../server-chat-item/types/server-chat-item-props';

export const SidePeekComponentRegistry: {
  [K in keyof SidePeekComponentPropsMap]: {
    component: React.ComponentType<SidePeekComponentPropsMap[K]>;
    props: SidePeekComponentPropsMap[K];
  };
} = {
  [SidePeekComponentRegistrationEnum.BACKPACK]: {
    component: BackPack,
    props: {} as BackpackProps,
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
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_TRAVERSE]: {
    component: Traverse,
    props: {} as TraversePropsDefinition,
  },
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_SET_SAIL]: {
    component: SetSail,
    props: {} as SetSailProps,
  },
  [SidePeekComponentRegistrationEnum.MAP_ACTIONS_CONJURE]: {
    component: Conjure,
    props: {} as ConjureProps,
  },
  [SidePeekComponentRegistrationEnum.SERVER_CHAT_ITEM]: {
    component: ServerChatItem,
    props: {} as ServerChatItemProps,
  },
  [SidePeekComponentRegistrationEnum.CRAFTED_ITEM]: {
    component: CraftedItem,
    props: {} as CraftedItemProps,
  },
  [SidePeekComponentRegistrationEnum.ITEM_DETAILS]: {
    component: ItemDetails,
    props: {} as ItemDetailsProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_LOG_ENTRY]: {
    component: LogEntrySidePeek,
    props: {} as LogEntrySidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_BUG_REPORT]: {
    component: BugReportSidePeek,
    props: {} as BugReportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_COORDINATE]: {
    component: GameMapCoordinateSidePeek,
    props: {} as GameMapCoordinateSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_LOCATION]: {
    component: GameMapLocationSidePeek,
    props: {} as GameMapLocationSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_NPC]: {
    component: GameMapNpcSidePeek,
    props: {} as GameMapNpcSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_KINGDOM]: {
    component: GameMapKingdomSidePeek,
    props: {} as GameMapKingdomSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_FORM]: {
    component: GameMapFormSidePeek,
    props: {} as GameMapFormSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_IMPORT]: {
    component: GameMapImportSidePeek,
    props: {} as GameMapImportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_DETAIL]: {
    component: AdminGameMapDetailSidePeek,
    props: {} as AdminGameMapDetailSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_LOCATIONS]: {
    component: GameMapRelatedLocationsSidePeek,
    props: {} as GameMapRelatedLocationsSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_NPCS]: {
    component: GameMapRelatedNpcsSidePeek,
    props: {} as GameMapRelatedNpcsSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_MONSTERS]: {
    component: GameMapRelatedMonstersSidePeek,
    props: {} as GameMapRelatedMonstersSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUESTS]: {
    component: GameMapRelatedQuestsSidePeek,
    props: {} as GameMapRelatedQuestsSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUEST_ITEMS]: {
    component: GameMapRelatedQuestItemsSidePeek,
    props: {} as GameMapRelatedQuestItemsSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_LOCATION_FORM]: {
    component: LocationFormSidePeek,
    props: {} as LocationFormSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_LOCATION_IMPORT]: {
    component: LocationImportSidePeek,
    props: {} as LocationImportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_LOCATION_DETAIL]: {
    component: AdminLocationDetailSidePeek,
    props: {} as AdminLocationDetailSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_NPC_FORM]: {
    component: NpcFormSidePeek,
    props: {} as NpcFormSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_NPC_IMPORT]: {
    component: NpcImportSidePeek,
    props: {} as NpcImportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_NPC_DETAIL]: {
    component: AdminNpcDetailSidePeek,
    props: {} as AdminNpcDetailSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL]: {
    component: AdminItemDetailSidePeek,
    props: {} as AdminItemDetailSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_ITEM_FORM]: {
    component: ItemFormSidePeek,
    props: {} as ItemFormSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_ITEM_IMPORT]: {
    component: ItemImportSidePeek,
    props: {} as ItemImportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_QUEST_DETAIL]: {
    component: AdminQuestDetailSidePeek,
    props: {} as AdminQuestDetailSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_MONSTER_DETAIL]: {
    component: AdminMonsterDetailSidePeek,
    props: {} as AdminMonsterDetailSidePeekProps,
  },
  // Add more components here
};
