import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';
import ReactMarkdown from 'react-markdown';

import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import GameMapRelatedDataCompactNav from './game-map-related-data-compact-nav';
import AdminGameMapDetailSidePeekProps from './types/admin-game-map-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { GameMapApiMessages } from '../../api/enums/game-map-api-messages';
import { useGameMap } from '../../api/hooks/use-game-map';
import { GAME_MAP_EVENT_TYPE_LABELS } from '../../enums/game-map-event-type';
import { GameMapRelatedDataKey } from '../../types/game-map-related-data-entry';
import { resolveGameMapBonusEntries } from '../../utils/resolve-game-map-bonus-entries';
import { resolveRequiredQuestItemCopy } from '../../utils/resolve-required-quest-item-copy';
import GameMapFormContent from '../forms/game-map-form-content';
import GameMapKingdomColorSwatch from '../game-map-kingdom-color-swatch';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin Game Map detail side-peek: stacks the same factual Game Map fields
 * the standalone Game Map show screen renders (map preview, description,
 * access/configuration, required Quest Item, bonuses) with an Admin-only
 * Edit action, so other modernized Admin resources (Quest, Monster,
 * Location, NPC) can open a real Game Map's identity without leaving their
 * own side-peek context.
 */
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

  const renderMapPreview = (): ReactNode => {
    if (!gameMap) {
      return null;
    }

    return (
      <div className="border-glacier-200 bg-glacier-950 dark:border-glacier-800 aspect-square w-full overflow-hidden rounded-md border">
        <img
          src={gameMap.map_url}
          alt={`${gameMap.name} map`}
          className="h-full w-full object-contain"
        />
      </div>
    );
  };

  const renderDescription = (): ReactNode => {
    if (!gameMap?.description) {
      return null;
    }

    return (
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-semibold">
          Description
        </h2>
        <div className="text-glacier-700 dark:text-glacier-300 min-w-0 text-sm break-words">
          <ReactMarkdown>{gameMap.description}</ReactMarkdown>
        </div>
      </section>
    );
  };

  const renderRequiredQuestItem = (): ReactNode => {
    if (!gameMap?.required_quest_item) {
      return null;
    }

    return (
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-semibold">
          Required Quest Item
        </h2>
        <p className="text-glacier-900 dark:text-glacier-100 font-medium">
          {gameMap.required_quest_item.name}
        </p>
        <p className="text-glacier-700 dark:text-glacier-300 text-sm">
          {resolveRequiredQuestItemCopy(gameMap.required_quest_item)}
        </p>
      </section>
    );
  };

  const renderAccessConfiguration = (): ReactNode => {
    if (!gameMap) {
      return null;
    }

    const hasRows =
      gameMap.default ||
      gameMap.can_traverse ||
      gameMap.event_restriction !== null ||
      gameMap.required_location !== null ||
      Boolean(gameMap.kingdom_color);

    if (!hasRows) {
      return null;
    }

    return (
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Access and Configuration
        </h2>
        <Dl>
          {gameMap.default && (
            <>
              <Dt>Default map</Dt>
              <Dd>Yes</Dd>
            </>
          )}
          {gameMap.can_traverse && (
            <>
              <Dt>Can traverse</Dt>
              <Dd>Yes</Dd>
            </>
          )}
          {gameMap.event_restriction !== null && (
            <>
              <Dt>Event restriction</Dt>
              <Dd>{GAME_MAP_EVENT_TYPE_LABELS[gameMap.event_restriction]}</Dd>
            </>
          )}
          {gameMap.required_location && (
            <>
              <Dt>Required Location</Dt>
              <Dd>{gameMap.required_location.name}</Dd>
            </>
          )}
          {gameMap.kingdom_color && (
            <>
              <Dt>Kingdom color</Dt>
              <Dd>
                <GameMapKingdomColorSwatch color={gameMap.kingdom_color} />
              </Dd>
            </>
          )}
        </Dl>
      </section>
    );
  };

  const renderMapBonuses = (): ReactNode => {
    if (!gameMap) {
      return null;
    }

    const bonusEntries = resolveGameMapBonusEntries(gameMap);

    if (bonusEntries.length === 0) {
      return null;
    }

    return (
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Map Bonuses
        </h2>
        <Dl>
          {bonusEntries.map((entry) => (
            <React.Fragment key={entry.label}>
              <Dt>{entry.label}</Dt>
              <Dd>{entry.percentage}%</Dd>
            </React.Fragment>
          ))}
        </Dl>
      </section>
    );
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

        {renderMapPreview()}

        {renderDescription()}

        {renderAccessConfiguration()}

        {renderRequiredQuestItem()}

        {renderMapBonuses()}

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
