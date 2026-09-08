import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import GameMapRelatedDataCompactNav from './game-map-related-data-compact-nav';
import AdminGameMapDetailSidePeekProps from './types/admin-game-map-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import GameMapDetail from '../../../../game/reusable-components/game-map/components/game-map-detail';
import { GameMapApiMessages } from '../../api/enums/game-map-api-messages';
import { useGameMap } from '../../api/hooks/use-game-map';
import { GameMapRelatedDataKey } from '../../types/game-map-related-data-entry';
import GameMapFormContent from '../forms/game-map-form-content';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminGameMapDetailSidePeek = ({
  game_map_id: gameMapId,
  on_game_map_changed: onGameMapChanged,
}: AdminGameMapDetailSidePeekProps): ReactNode => {
  const { game_map: gameMap, loading, error, refresh } = useGameMap(gameMapId);
  const [showEdit, setShowEdit] = useState(false);
  const [announcement, setAnnouncement] = useState('');
  const [relatedSelection, setRelatedSelection] =
    useState<GameMapRelatedDataKey | null>(null);

  const isStackActive = showEdit || relatedSelection !== null;

  const handleEdit = (): void => {
    setShowEdit(true);
  };

  const handleCloseEdit = (): void => {
    setShowEdit(false);
  };

  const handleSaved = (): void => {
    refresh();
    onGameMapChanged?.();
    setShowEdit(false);
    setAnnouncement(GameMapSidePeekMessages.GameMapSaved);
  };

  const handleOpenRelated = (key: GameMapRelatedDataKey): void => {
    setRelatedSelection(key);
  };

  const handleCloseRelated = (): void => {
    setRelatedSelection(null);
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !gameMap) {
      return (
        <ApiErrorAlert apiError={error?.message ?? GameMapApiMessages.Load} />
      );
    }

    return (
      <div className="space-y-4">
        <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
          {gameMap.name}
        </h1>

        <div className="flex justify-center py-2">
          <Button
            label="Edit Game Map"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        <GameMapDetail game_map={gameMap} />

        <section>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
            Related Game Data
          </h2>
          <GameMapRelatedDataCompactNav
            game_map_id={gameMapId}
            on_open_related={handleOpenRelated}
          />
        </section>
      </div>
    );
  };

  const renderEdit = (): ReactNode => {
    if (!showEdit) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseEdit} aria_label="Edit Game Map">
        <GameMapFormContent
          game_map_id={gameMapId}
          on_saved={handleSaved}
          on_cancel={handleCloseEdit}
          embedded
        />
      </StackedCard>
    );
  };

  const renderRelatedDataSelectionContent = (
    key: GameMapRelatedDataKey
  ): ReactNode => {
    switch (key) {
      case 'locations': {
        const RelatedLocations = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_LOCATIONS
        );

        return (
          <RelatedLocations
            is_open
            title="Related Locations"
            game_map_id={gameMapId}
          />
        );
      }
      case 'npcs': {
        const RelatedNpcs = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_NPCS
        );

        return (
          <RelatedNpcs is_open title="Related NPCs" game_map_id={gameMapId} />
        );
      }
      case 'monsters': {
        const RelatedMonsters = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_MONSTERS
        );

        return (
          <RelatedMonsters
            is_open
            title="Related Monsters"
            game_map_id={gameMapId}
          />
        );
      }
      case 'quests': {
        const RelatedQuests = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUESTS
        );

        return (
          <RelatedQuests
            is_open
            title="Related Quests"
            game_map_id={gameMapId}
          />
        );
      }
      case 'quest-items': {
        const RelatedQuestItems = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_RELATED_QUEST_ITEMS
        );

        return (
          <RelatedQuestItems
            is_open
            title="Related Quest Items"
            game_map_id={gameMapId}
          />
        );
      }
    }
  };

  const resolveRelatedSelectionLabel = (key: GameMapRelatedDataKey): string => {
    switch (key) {
      case 'locations':
        return 'Related Locations';
      case 'npcs':
        return 'Related NPCs';
      case 'monsters':
        return 'Related Monsters';
      case 'quests':
        return 'Related Quests';
      case 'quest-items':
        return 'Related Quest Items';
    }
  };

  const renderRelatedDataSelection = (): ReactNode => {
    if (!relatedSelection) {
      return null;
    }

    return (
      <StackedCard
        on_close={handleCloseRelated}
        aria_label={resolveRelatedSelectionLabel(relatedSelection)}
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        {renderRelatedDataSelectionContent(relatedSelection)}
      </StackedCard>
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      <div
        className={clsx(
          'min-h-0 flex-1 px-4 py-4 sm:px-5',
          isStackActive ? 'overflow-hidden' : 'overflow-y-auto'
        )}
      >
        {renderContent()}
      </div>
      {renderEdit()}
      {renderRelatedDataSelection()}
    </div>
  );
};

export default AdminGameMapDetailSidePeek;
