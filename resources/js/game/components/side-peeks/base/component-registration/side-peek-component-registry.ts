import React from 'react';

import { SidePeekComponentPropsMap } from './side-peek-component-props-map';
import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import ClassMasteryImportSidePeek from '../../../../../admin/class-masteries/components/side-peeks/class-mastery-import-side-peek';
import ClassMasteryImportSidePeekProps from '../../../../../admin/class-masteries/components/side-peeks/types/class-mastery-import-side-peek-props';
import ClassImportSidePeek from '../../../../../admin/classes/components/side-peeks/class-import-side-peek';
import ClassImportSidePeekProps from '../../../../../admin/classes/components/side-peeks/types/class-import-side-peek-props';
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
import AdminLocationGemDetailSidePeek from '../../../../../admin/location-gems/components/side-peeks/admin-location-gem-detail-side-peek';
import LocationGemBulkRollResultSidePeek from '../../../../../admin/location-gems/components/side-peeks/location-gem-bulk-roll-result-side-peek';
import LocationGemImportSidePeek from '../../../../../admin/location-gems/components/side-peeks/location-gem-import-side-peek';
import AdminLocationGemDetailSidePeekProps from '../../../../../admin/location-gems/components/side-peeks/types/admin-location-gem-detail-side-peek-props';
import LocationGemBulkRollResultSidePeekProps from '../../../../../admin/location-gems/components/side-peeks/types/location-gem-bulk-roll-result-side-peek-props';
import LocationGemImportSidePeekProps from '../../../../../admin/location-gems/components/side-peeks/types/location-gem-import-side-peek-props';
import AdminLocationDetailSidePeek from '../../../../../admin/locations/components/side-peeks/admin-location-detail-side-peek';
import LocationFormSidePeek from '../../../../../admin/locations/components/side-peeks/location-form-side-peek';
import LocationImportSidePeek from '../../../../../admin/locations/components/side-peeks/location-import-side-peek';
import AdminLocationDetailSidePeekProps from '../../../../../admin/locations/components/side-peeks/types/admin-location-detail-side-peek-props';
import LocationFormSidePeekProps from '../../../../../admin/locations/components/side-peeks/types/location-form-side-peek-props';
import LocationImportSidePeekProps from '../../../../../admin/locations/components/side-peeks/types/location-import-side-peek-props';
import AdminMapGemDetailSidePeek from '../../../../../admin/map-gems/components/side-peeks/admin-map-gem-detail-side-peek';
import MapGemBulkRollResultSidePeek from '../../../../../admin/map-gems/components/side-peeks/map-gem-bulk-roll-result-side-peek';
import MapGemImportSidePeek from '../../../../../admin/map-gems/components/side-peeks/map-gem-import-side-peek';
import AdminMapGemDetailSidePeekProps from '../../../../../admin/map-gems/components/side-peeks/types/admin-map-gem-detail-side-peek-props';
import MapGemBulkRollResultSidePeekProps from '../../../../../admin/map-gems/components/side-peeks/types/map-gem-bulk-roll-result-side-peek-props';
import MapGemImportSidePeekProps from '../../../../../admin/map-gems/components/side-peeks/types/map-gem-import-side-peek-props';
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
import RaceImportSidePeek from '../../../../../admin/races/components/side-peeks/race-import-side-peek';
import RaceImportSidePeekProps from '../../../../../admin/races/components/side-peeks/types/race-import-side-peek-props';
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
import CharacterClassRankDetailSidePeek from '../../game-data/character-class-rank-detail-side-peek';
import CharacterClassSpecialtyDetailSidePeek from '../../game-data/character-class-specialty-detail-side-peek';
import ClassDetailSidePeek from '../../game-data/class-detail-side-peek';
import ClassMasteryDetailSidePeek from '../../game-data/class-mastery-detail-side-peek';
import PlayerGameMapDetailSidePeek from '../../game-data/player-game-map-detail-side-peek';
import PlayerNpcDetailSidePeek from '../../game-data/player-npc-detail-side-peek';
import CharacterClassRankDetailSidePeekProps from '../../game-data/types/character-class-rank-detail-side-peek-props';
import CharacterClassSpecialtyDetailSidePeekProps from '../../game-data/types/character-class-specialty-detail-side-peek-props';
import ClassDetailSidePeekProps from '../../game-data/types/class-detail-side-peek-props';
import ClassMasteryDetailSidePeekProps from '../../game-data/types/class-mastery-detail-side-peek-props';
import PlayerGameMapDetailSidePeekProps from '../../game-data/types/player-game-map-detail-side-peek-props';
import PlayerNpcDetailSidePeekProps from '../../game-data/types/player-npc-detail-side-peek-props';
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
import { SidePeekContentScrollMode } from '../enums/side-peek-content-scroll-mode';

export const SidePeekComponentRegistry: {
  [K in keyof SidePeekComponentPropsMap]: {
    component: React.ComponentType<SidePeekComponentPropsMap[K]>;
    props: SidePeekComponentPropsMap[K];
    content_scroll_mode?: SidePeekContentScrollMode;
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
  [SidePeekComponentRegistrationEnum.PLAYER_GAME_MAP_DETAIL]: {
    component: PlayerGameMapDetailSidePeek,
    props: {} as PlayerGameMapDetailSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.PLAYER_NPC_DETAIL]: {
    component: PlayerNpcDetailSidePeek,
    props: {} as PlayerNpcDetailSidePeekProps,
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
  [SidePeekComponentRegistrationEnum.CLASS_DETAIL]: {
    component: ClassDetailSidePeek,
    props: {} as ClassDetailSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.CLASS_MASTERY_DETAIL]: {
    component: ClassMasteryDetailSidePeek,
    props: {} as ClassMasteryDetailSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.CHARACTER_CLASS_RANK_DETAIL]: {
    component: CharacterClassRankDetailSidePeek,
    props: {} as CharacterClassRankDetailSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.CHARACTER_CLASS_SPECIALTY_DETAIL]: {
    component: CharacterClassSpecialtyDetailSidePeek,
    props: {} as CharacterClassSpecialtyDetailSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
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
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_NPC]: {
    component: GameMapNpcSidePeek,
    props: {} as GameMapNpcSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
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
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_LOCATIONS]: {
    component: GameMapRelatedLocationsSidePeek,
    props: {} as GameMapRelatedLocationsSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_NPCS]: {
    component: GameMapRelatedNpcsSidePeek,
    props: {} as GameMapRelatedNpcsSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_MONSTERS]: {
    component: GameMapRelatedMonstersSidePeek,
    props: {} as GameMapRelatedMonstersSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUESTS]: {
    component: GameMapRelatedQuestsSidePeek,
    props: {} as GameMapRelatedQuestsSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUEST_ITEMS]: {
    component: GameMapRelatedQuestItemsSidePeek,
    props: {} as GameMapRelatedQuestItemsSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
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
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
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
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL]: {
    component: AdminItemDetailSidePeek,
    props: {} as AdminItemDetailSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
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
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_MONSTER_DETAIL]: {
    component: AdminMonsterDetailSidePeek,
    props: {} as AdminMonsterDetailSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_CLASS_IMPORT]: {
    component: ClassImportSidePeek,
    props: {} as ClassImportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_RACE_IMPORT]: {
    component: RaceImportSidePeek,
    props: {} as RaceImportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_CLASS_MASTERY_IMPORT]: {
    component: ClassMasteryImportSidePeek,
    props: {} as ClassMasteryImportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_MAP_GEM_IMPORT]: {
    component: MapGemImportSidePeek,
    props: {} as MapGemImportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_MAP_GEM_DETAIL]: {
    component: AdminMapGemDetailSidePeek,
    props: {} as AdminMapGemDetailSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_MAP_GEM_BULK_ROLL_RESULT]: {
    component: MapGemBulkRollResultSidePeek,
    props: {} as MapGemBulkRollResultSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_LOCATION_GEM_IMPORT]: {
    component: LocationGemImportSidePeek,
    props: {} as LocationGemImportSidePeekProps,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_LOCATION_GEM_DETAIL]: {
    component: AdminLocationGemDetailSidePeek,
    props: {} as AdminLocationGemDetailSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  [SidePeekComponentRegistrationEnum.ADMIN_LOCATION_GEM_BULK_ROLL_RESULT]: {
    component: LocationGemBulkRollResultSidePeek,
    props: {} as LocationGemBulkRollResultSidePeekProps,
    content_scroll_mode: SidePeekContentScrollMode.COMPONENT,
  },
  // Add more components here
};
