import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import GameMapRelatedDataEntry, {
  GameMapRelatedDataKey,
} from '../types/game-map-related-data-entry';

export const useGameMapRelatedDataEntries = (
  gameMapId: number,
  onOpenRelated?: (key: GameMapRelatedDataKey) => void
): GameMapRelatedDataEntry[] => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openRelated = (
    key: GameMapRelatedDataKey,
    component: SidePeekComponentRegistrationEnum,
    title: string
  ): void => {
    if (onOpenRelated) {
      onOpenRelated(key);

      return;
    }

    sidePeekEmitter.emit(SidePeekEventType.SIDE_PEEK, component, {
      is_open: true,
      title,
      allow_clicking_outside: true,
      game_map_id: gameMapId,
    });
  };

  return [
    {
      key: 'locations',
      label: 'Locations',
      mobile_label: 'Locations',
      icon_class: 'fas fa-map-marker-alt',
      on_click: () =>
        openRelated(
          'locations',
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_LOCATIONS,
          'Locations'
        ),
    },
    {
      key: 'npcs',
      label: 'NPCs',
      mobile_label: 'NPCs',
      icon_class: 'fas fa-user',
      on_click: () =>
        openRelated(
          'npcs',
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_NPCS,
          'NPCs'
        ),
    },
    {
      key: 'monsters',
      label: 'Monsters',
      mobile_label: 'Monsters',
      icon_class: 'fas fa-skull',
      on_click: () =>
        openRelated(
          'monsters',
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_MONSTERS,
          'Monsters'
        ),
    },
    {
      key: 'quests',
      label: 'Quests',
      mobile_label: 'Quests',
      icon_class: 'ra ra-scroll-unfurled',
      on_click: () =>
        openRelated(
          'quests',
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUESTS,
          'Quests'
        ),
    },
    {
      key: 'quest-items',
      label: 'Quest Items',
      mobile_label: 'Items',
      icon_class: 'ra ra-bone-knife',
      on_click: () =>
        openRelated(
          'quest-items',
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUEST_ITEMS,
          'Quest Items'
        ),
    },
  ];
};
