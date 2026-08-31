import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';
import ReactMarkdown from 'react-markdown';

import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import AdminGameMapDetailSidePeekProps from './types/admin-game-map-detail-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import { GameMapApiMessages } from '../../api/enums/game-map-api-messages';
import { useGameMap } from '../../api/hooks/use-game-map';
import { GameMapCopy } from '../../enums/game-map-copy';
import { GAME_MAP_EVENT_TYPE_LABELS } from '../../enums/game-map-event-type';
import { convertStoredBonusToPercentage } from '../../utils/convert-stored-bonus-to-percentage';
import { resolveRequiredQuestItemCopy } from '../../utils/resolve-required-quest-item-copy';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
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
  const sidePeekEmitter = useSidePeekEmitter();
  const { game_map: gameMap, loading, error, refresh } = useGameMap(gameMapId);
  const [announcement, setAnnouncement] = useState('');

  const handleEdit = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_FORM,
      {
        is_open: true,
        title: 'Edit Game Map',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
        on_saved: () => {
          refresh();
          onGameMapChanged?.();
          setAnnouncement(GameMapSidePeekMessages.GameMapSaved);
        },
      }
    );
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
      return (
        <p className="text-glacier-700 dark:text-glacier-300 text-sm">
          {GameMapCopy.NoDescription}
        </p>
      );
    }

    return (
      <div className="text-glacier-700 dark:text-glacier-300 min-w-0 text-sm break-words">
        <ReactMarkdown>{gameMap.description}</ReactMarkdown>
      </div>
    );
  };

  const renderRequiredQuestItem = (): ReactNode => {
    if (!gameMap) {
      return null;
    }

    if (!gameMap.required_quest_item) {
      return (
        <section>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-semibold">
            Required Quest Item
          </h2>
          <p className="text-glacier-700 dark:text-glacier-300 text-sm">
            {resolveRequiredQuestItemCopy(null)}
          </p>
        </section>
      );
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

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !gameMap) {
      return (
        <div className="px-4">
          <ApiErrorAlert apiError={error?.message ?? GameMapApiMessages.Load} />
        </div>
      );
    }

    return (
      <div className="space-y-4 px-4">
        <div className="flex justify-center py-2">
          <Button
            label="Edit Game Map"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        {renderMapPreview()}

        <section>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-semibold">
            Description
          </h2>
          {renderDescription()}
        </section>

        <section>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
            Access and Configuration
          </h2>
          <Dl>
            <Dt>Default map</Dt>
            <Dd>{gameMap.default ? 'Yes' : 'No'}</Dd>
            <Dt>Can traverse</Dt>
            <Dd>{gameMap.can_traverse ? 'Yes' : 'No'}</Dd>
            <Dt>Event restriction</Dt>
            <Dd>
              {gameMap.event_restriction === null
                ? 'None'
                : GAME_MAP_EVENT_TYPE_LABELS[gameMap.event_restriction]}
            </Dd>
            <Dt>Required Location</Dt>
            <Dd>{gameMap.required_location?.name ?? 'None'}</Dd>
            <Dt>Kingdom color</Dt>
            <Dd>{gameMap.kingdom_color}</Dd>
          </Dl>
        </section>

        {renderRequiredQuestItem()}

        <section>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
            Map Bonuses
          </h2>
          <Dl>
            <Dt>XP Bonus</Dt>
            <Dd>{convertStoredBonusToPercentage(gameMap.xp_bonus ?? 0)}%</Dd>
            <Dt>Skill XP Bonus</Dt>
            <Dd>
              {convertStoredBonusToPercentage(
                gameMap.skill_training_bonus ?? 0
              )}
              %
            </Dd>
            <Dt>Drop Chance Bonus</Dt>
            <Dd>
              {convertStoredBonusToPercentage(gameMap.drop_chance_bonus ?? 0)}%
            </Dd>
            <Dt>Enemy Stat Increase</Dt>
            <Dd>
              {convertStoredBonusToPercentage(gameMap.enemy_stat_bonus ?? 0)}%
            </Dd>
            <Dt>Character Damage Deduction</Dt>
            <Dd>
              {convertStoredBonusToPercentage(
                gameMap.character_attack_reduction ?? 0
              )}
              %
            </Dd>
          </Dl>
        </section>
      </div>
    );
  };

  return (
    <>
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
    </>
  );
};

export default AdminGameMapDetailSidePeek;
