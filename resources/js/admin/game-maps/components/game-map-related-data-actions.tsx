import React, { ReactNode } from 'react';

import GameMapRelatedDataActionsProps from './types/game-map-related-data-actions-props';
import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

/**
 * "Related Game Data" entry points for a Game Map's factual detail: opens
 * a bounded relationship browser SidePeek for each interconnected resource
 * (Locations, NPCs, Monsters, Quests, Quest Items). Belongs in factual Map
 * detail, not the map editor toolbar.
 */
const GameMapRelatedDataActions = ({
  game_map_id: gameMapId,
}: GameMapRelatedDataActionsProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();

  const handleOpenLocations = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_LOCATIONS,
      {
        is_open: true,
        title: 'Locations',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
      }
    );
  };

  const handleOpenNpcs = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_NPCS,
      {
        is_open: true,
        title: 'NPCs',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
      }
    );
  };

  const handleOpenMonsters = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_MONSTERS,
      {
        is_open: true,
        title: 'Monsters',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
      }
    );
  };

  const handleOpenQuests = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUESTS,
      {
        is_open: true,
        title: 'Quests',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
      }
    );
  };

  const handleOpenQuestItems = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUEST_ITEMS,
      {
        is_open: true,
        title: 'Quest Items',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
      }
    );
  };

  return (
    <section>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Related Game Data
      </h2>
      <div className="flex flex-wrap gap-2">
        <Button
          label="Locations"
          variant={ButtonVariant.PRIMARY}
          additional_css="text-sm px-3 py-1.5"
          on_click={handleOpenLocations}
        />
        <Button
          label="NPCs"
          variant={ButtonVariant.PRIMARY}
          additional_css="text-sm px-3 py-1.5"
          on_click={handleOpenNpcs}
        />
        <Button
          label="Monsters"
          variant={ButtonVariant.PRIMARY}
          additional_css="text-sm px-3 py-1.5"
          on_click={handleOpenMonsters}
        />
        <Button
          label="Quests"
          variant={ButtonVariant.PRIMARY}
          additional_css="text-sm px-3 py-1.5"
          on_click={handleOpenQuests}
        />
        <Button
          label="Quest Items"
          variant={ButtonVariant.PRIMARY}
          additional_css="text-sm px-3 py-1.5"
          on_click={handleOpenQuestItems}
        />
      </div>
    </section>
  );
};

export default GameMapRelatedDataActions;
