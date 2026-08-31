import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';
import ReactMarkdown from 'react-markdown';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { GameMapApiMessages } from '../api/enums/game-map-api-messages';
import { useGameMap } from '../api/hooks/use-game-map';
import GameMapRelatedDataActions from '../components/game-map-related-data-actions';
import { GameMapSidePeekMessages } from '../components/side-peeks/enums/game-map-side-peek-messages';
import { GameMapCopy } from '../enums/game-map-copy';
import { GAME_MAP_EVENT_TYPE_LABELS } from '../enums/game-map-event-type';
import { GameMapScreens } from '../screen-manager/game-map-screen-constants';
import { useGameMapScreenNavigation } from '../screen-manager/game-map-screen-kit';
import { GameMapShowScreenProps } from '../screen-manager/game-map-screen-props';
import { convertStoredBonusToPercentage } from '../utils/convert-stored-bonus-to-percentage';
import { resolveRequiredQuestItemCopy } from '../utils/resolve-required-quest-item-copy';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GameMapShowScreen = ({
  game_map_id: gameMapId,
}: GameMapShowScreenProps): ReactNode => {
  const navigation = useGameMapScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const { game_map: gameMap, loading, error, refresh } = useGameMap(gameMapId);
  const [announcement, setAnnouncement] = useState('');
  const hasTiles = (gameMap?.tiles.length ?? 0) > 0;

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEditLocations = (): void => {
    if (!hasTiles) {
      return;
    }

    navigation.navigateTo(GameMapScreens.EDITOR, { game_map_id: gameMapId });
  };

  const handleMapSaved = (): void => {
    refresh();
    setAnnouncement(GameMapSidePeekMessages.GameMapSaved);
  };

  const handleEditMap = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_FORM,
      {
        is_open: true,
        title: 'Edit Game Map',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
        on_saved: handleMapSaved,
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

  const renderEditorAction = (): ReactNode => {
    if (!hasTiles) {
      return null;
    }

    return (
      <Button
        label="Edit Locations"
        variant={ButtonVariant.PRIMARY}
        on_click={handleEditLocations}
      />
    );
  };

  const renderProcessingStatus = (): ReactNode => {
    if (hasTiles) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        Map tile processing is not complete. The coordinate editor will be
        available after processing finishes and this page is refreshed.
      </Alert>
    );
  };

  const renderDetails = (): ReactNode => {
    if (!gameMap) {
      return null;
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-end gap-3">
          <Button
            label="Edit Map"
            variant={ButtonVariant.PRIMARY}
            on_click={handleEditMap}
          />
          {renderEditorAction()}
        </div>

        {renderProcessingStatus()}

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
      <div className="flex flex-col gap-6">
        <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
          {gameMap.name}
        </h1>
        <div className="grid gap-6 md:grid-cols-2">
          {renderMapPreview()}
          {renderDetails()}
        </div>
        <GameMapRelatedDataActions game_map_id={gameMapId} />
      </div>
    );
  };

  return (
    <AdminPage
      title={gameMap?.name ?? 'Game Map'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
    </AdminPage>
  );
};

export default GameMapShowScreen;
